ALTER TABLE mail_user
    ADD COLUMN IF NOT EXISTS nc_enabled    enum ('n','y') NOT NULL DEFAULT 'y',
    ADD COLUMN IF NOT EXISTS nc_quota      varchar(255)   NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS nc_group      varchar(255)   NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS nc_adm_user    enum ('n','y') NOT NULL DEFAULT 'n',
    ADD COLUMN IF NOT EXISTS nc_server     enum ('n','y') NOT NULL DEFAULT 'y',
    ADD COLUMN IF NOT EXISTS nc_adm_server enum ('n','y') NOT NULL DEFAULT 'n',
    ADD COLUMN IF NOT EXISTS nc_domain     enum ('n','y') NOT NULL DEFAULT 'y',
    ADD COLUMN IF NOT EXISTS nc_adm_domain enum ('n','y') NOT NULL DEFAULT 'n';