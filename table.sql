-- This table definition is loaded and then executed when the OpenEMR interface's install button is clicked.
CREATE TABLE IF NOT EXISTS `oce_notification_banner` (
    `id` INT(11)  PRIMARY KEY AUTO_INCREMENT NOT NULL,
    `message` TEXT default '' COMMENT 'the message to display in the banner',
    `is_active` boolean COMMENT 'whether to show the banner or not',
    -- FIXME: I can't use date ranges until I can figure out the appropriate GlobalSettings data type
    -- `active_at` datetime default null COMMENT 'show banner after active_at date, but not after inactive_at date if that is not null',
    -- `inactive_at` datetime default null COMMENT 'show banner after active_at date, but not after inactive_at date if that is not null' 
    `created_at` datetime COMMENT 'when this record was created',
    `updated_at` datetime COMMENT 'when this record was last changed'
);
