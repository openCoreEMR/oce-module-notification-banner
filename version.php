<?php

/**
 * Package version data for the OpenCoreEMR Notification Banner Module
 *
 * Determines the running version from git describe when in a checkout, and
 * falls back to the default_* values below when not (e.g. inside the OpenEMR
 * modules tree). The defaults are kept in lockstep with
 * .release-please-manifest.json via release-please's x-release-please-*
 * annotations.
 *
 * @package   OpenCoreEMR
 * @link      http://www.open-emr.org
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2025 OpenCoreEMR Inc
 * @license   GNU General Public License 3
 */

declare(strict_types=1);

// Calculate and unpack version information into global variables that OpenEMR expects
[$v_major, $v_minor, $v_patch, $v_tag, $v_database] = (function (
    string $default_major,
    string $default_minor,
    string $default_patch
): array {
    /**
     * Execute an allowed git subcommand in the module directory via proc_open (no shell).
     * Only 'describe' and 'rev-parse' with fixed args are allowed to prevent command injection.
     *
     * @param 'describe'|'rev-parse' $subcommand Allowed git subcommand
     * @param array<int, string> $allowedArgs Fixed list of arguments for that subcommand
     * @return string Command output trimmed, or empty string on failure
     */
    $executeGitCommand = function (string $subcommand, array $allowedArgs): string {
        $allowed = [
            'describe' => ['--tags', '--always', '--dirty'],
            'rev-parse' => ['--abbrev-ref', 'HEAD'],
        ];
        if (!isset($allowed[$subcommand]) || $allowedArgs !== $allowed[$subcommand]) {
            return '';
        }

        $cmd = array_merge(['git', '-C', __DIR__, $subcommand], $allowedArgs);
        // nosemgrep: php.lang.security.exec-use.exec-use - no shell; cmd is allowlisted
        $proc = proc_open(
            $cmd,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        if (!is_resource($proc)) {
            return '';
        }

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $return_code = proc_close($proc);

        return $return_code === 0 ? trim((string) $stdout) : '';
    };

    $git_dir = __DIR__ . '/.git';
    if (is_dir($git_dir) || is_file($git_dir)) {
        // We're in a git repository - use git describe for version
        $git_describe = $executeGitCommand('describe', ['--tags', '--always', '--dirty']);

        if ($git_describe !== '') {
            // Parse git describe output (e.g., "v1.0.0-5-gabc1234-dirty" or "abc1234-dirty")
            // Format: [tag]-[commits since tag]-g[short hash][-dirty]
            if (preg_match('/^v?(\d+)\.(\d+)\.(\d+)/', $git_describe, $matches)) {
                // Has a version tag - use it via array destructuring (skip index 0)
                [, $v_major, $v_minor, $v_patch] = $matches;

                // Add commit count and hash if there are commits after the tag
                if (preg_match('/-(\d+)-g([0-9a-f]+)(-dirty)?$/', $git_describe, $extra)) {
                    // Unpack with default for optional dirty flag
                    [, $commits, $hash, $dirty] = $extra + [3 => null];
                    $v_patch .= '-dev+' . $hash;
                    if ($dirty !== null) {
                        $v_patch .= '.dirty';
                    }
                } elseif (str_ends_with($git_describe, '-dirty')) {
                    $v_patch .= '-dirty';
                }
            } else {
                // No version tag - use commit hash
                $branch = $executeGitCommand('rev-parse', ['--abbrev-ref', 'HEAD']) ?: 'unknown';
                $v_major = $branch;
                $v_minor = $git_describe;
                $v_patch = '';
            }

            return [$v_major, $v_minor, $v_patch, '', 1];
        }
    }

    // Not in a git repository or git command failed - use default version
    return [$default_major, $default_minor, $default_patch, '', 1];
})(
    // The default_* values below are kept in lockstep with the canonical
    // version in .release-please-manifest.json. release-please rewrites
    // them on every release via the x-release-please-* annotations.
    default_major: '0', // x-release-please-major
    default_minor: '6', // x-release-please-minor
    default_patch: '4' // x-release-please-patch
);
