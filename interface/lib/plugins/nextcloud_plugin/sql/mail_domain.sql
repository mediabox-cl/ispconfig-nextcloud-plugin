ALTER TABLE mail_domain
    ADD COLUMN IF NOT EXISTS nc_enabled enum ('n','y') NOT NULL DEFAULT 'n',
    ADD COLUMN IF NOT EXISTS nc_quota   varchar(255)   NOT NULL DEFAULT '0',
    ADD COLUMN IF NOT EXISTS nc_group   varchar(255)   NOT NULL DEFAULT '';