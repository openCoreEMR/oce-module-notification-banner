<?php

/**
 * Initializes the OpenCoreEmr Notification Banner Module
 *
 * @package   OpenCoreEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc
 * @license   GNU General Public License 3
 */

namespace OpenCoreEMR\Modules\NotificationBanner;

/**
 * Note the below use statements are importing classes from the OpenEMR core codebase
 */
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Kernel;
use OpenEMR\Events\Appointments\AppointmentSetEvent;
use OpenEMR\Events\Core\TwigEnvironmentEvent;
use OpenEMR\Events\Globals\GlobalsInitializedEvent;
use OpenEMR\Events\Main\Tabs\RenderEvent;
use OpenEMR\Events\PatientDemographics\RenderEvent as pRenderEvent;
use OpenEMR\Events\RestApiExtend\RestApiCreateEvent;
use OpenEMR\Events\RestApiExtend\RestApiResourceServiceEvent;
use OpenEMR\Events\RestApiExtend\RestApiScopeEvent;
use OpenEMR\Menu\MenuEvent;
use OpenEMR\Services\Globals\GlobalSetting;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

class Bootstrap
{
    const MODULE_NAME = "oce-module-notification-banner";
    const GLOBAL_SETTINGS = [
        'message' => [
            'title' => 'Banner Message',
            'type' => GlobalSetting::DATA_TYPE_TEXT,
            'default' => '',
            'description' => 'The message to display in the maintenance banner'
        ],
        'is_active' => [
            'title' => 'Banner Toggle',
            'type' => GlobalSetting::DATA_TYPE_BOOL,
            'default' => '',
            'description' => 'The message will be displayed if this is true and the message is not blank.'
        ]
    ];

    /**
     * @var EventDispatcherInterface The object responsible for sending and subscribing to events through the OpenEMR system
     */
    private EventDispatcherInterface $eventDispatcher;

    /**
     * @var GlobalConfig Holds our module global configuration values that can be used throughout the module.
     */
    private GlobalConfig $globalsConfig;

    /**
     * @var string The folder name of the module.  Set dynamically from searching the filesystem.
     */
    private string $moduleDirectoryName;

    /**
     * @var \Twig\Environment The twig rendering environment
     */
    private \Twig\Environment $twig;

    /**
     * @var SystemLogger
     */
    private SystemLogger $logger;

    public function __construct(EventDispatcherInterface $eventDispatcher, ?Kernel $kernel = null)
    {
        global $GLOBALS;

        if (empty($kernel)) {
            $kernel = new Kernel();
        }
        $this->eventDispatcher = $eventDispatcher;
        $this->globalsConfig = new GlobalConfig($GLOBALS);
        $this->moduleDirectoryName = basename(dirname(__DIR__));
	
        // NOTE: eventually you will be able to pull the twig container directly from the kernel instead of instantiating
        // it here.
        $twig = new TwigContainer($this->getTemplatePath(), $kernel);
        $twigEnv = $twig->getTwig();
        $this->twig = $twigEnv;

        $this->logger = new SystemLogger();
        $this->logger->debug('Notification Banner Bootstrap constructed');
    }

    public function addGlobalSettings()
    {
        $this->eventDispatcher->addListener(GlobalsInitializedEvent::EVENT_HANDLE, [$this, 'addGlobalSettingsSection']);
    }

    public function addGlobalSettingsSection(GlobalsInitializedEvent $event)
    {
        global $GLOBALS;

        $service = $event->getGlobalsService();
        $section = xlt("OpenCoreEMR Notification Banner");
        $service->createSection($section, 'Banner');

        $settings = $this->globalsConfig->getGlobalSettingSectionConfiguration();

        foreach ($settings as $key => $config) {
            $value = $GLOBALS[$key] ?? $config['default'];
            $service->appendToSection(
                $section,
                $key,
                new GlobalSetting(
                    xlt($config['title']),
                    $config['type'],
                    $value,
                    xlt($config['description']),
                    true
                )
            );
        }
    }

    public function subscribeToEvents(): void
    {
        $this->addGlobalSettings();

        if (!$this->globalsConfig->isConfigured()) {
            $this->logger->debug('Notification Banner is not configured. Skipping subscribeToEvents.');
            return;
        }
        if (!$this->globalsConfig->isActive) {
            $this->logger->debug('Notifications Banner is inactive. Skipping subscribeToEvents.');
            return;
        }
        $this->logger->debug('Notification Banner subscribeToEvents called');
        $this->registerRenderEvent();
        $this->logger->debug('Notification Banner subscribed');
    }

    protected function registerRenderEvent()
    {
        $this->eventDispatcher->addListener(RenderEvent::EVENT_BODY_RENDER_PRE, [$this, 'renderNotificationBanner']);
    }

    /**
     * Renders the notification banner using twig template
     */
    public function renderNotificationBanner()
    {
        $message = $this->globalsConfig->message;
        if ($message) {
            try {
                echo $this->twig->render('notification-banner.html.twig', [
                    'message' => $message,
                    'banner_class' => 'notification-banner',
                    'message_class' => 'notification-message'
                ]);
            } catch (\Exception $e) {
                $this->logger->errorLogCaller('Failed to render notification banner template', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Fallback to basic HTML if template fails
                echo '<div style="background-color: #dc3545; color: white; padding: 1em; text-align: center;">';
                echo '<p style="margin: 0; font-weight: bold;">' . htmlspecialchars($message) . '</p>';
                echo '</div>';
            }
        }
    }

    /**
     * We tie into any events dealing with the templates / page rendering of the system here
     */
    public function registerTemplateEvents()
    {
        $this->logger->debug('Notification Banner registering template events.');
        $this->eventDispatcher->addListener(TwigEnvironmentEvent::EVENT_CREATED, [$this, 'addTemplateOverrideLoader']);
        $this->logger->debug('Notification Banner template events registered.');
    }

    /**
     * @param TwigEnvironmentEvent $event
     */
    public function addTemplateOverrideLoader(TwigEnvironmentEvent $event)
    {
        $this->logger->debug('Notification Banner adding template override loader');
        try {
            $twig = $event->getTwigEnvironment();
            if ($twig === $this->twig) {
                $this->logger->debug('Notification Banner Twig environment is already set up.');
                return;
            }
            // we make sure we can override our file system directory here.
            $loader = $twig->getLoader();
            if ($loader instanceof FilesystemLoader) {
                $loader->prependPath($this->getTemplatePath());
            }
            $this->logger->debug('Added Notification Banner template override loader');
        } catch (LoaderError $error) {
            $this->logger->errorLogCaller("Failed to create template loader", ['innerMessage' => $error->getMessage(), 'trace' => $error->getTraceAsString()]);
        }
    }

    public function getTemplatePath()
    {
        return \dirname(__DIR__) . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR;
    }
}
