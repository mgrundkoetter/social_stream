#
# Table structure for table 'tx_socialstream_domain_model_page'
#
CREATE TABLE tx_socialstream_domain_model_page (

  uid              INT(11)                         NOT NULL AUTO_INCREMENT,
  pid              INT(11) DEFAULT '0'             NOT NULL,

  id               VARCHAR(255) DEFAULT ''         NOT NULL,
  me               INT(11) DEFAULT '0'             NOT NULL,
  streamtype       INT(11) DEFAULT '0'             NOT NULL,
  token            VARCHAR(255) DEFAULT ''         NOT NULL,
  tokensecret      VARCHAR(255) DEFAULT ''         NOT NULL,
  expires          INT(11) UNSIGNED                NOT NULL DEFAULT '0',
  name             VARCHAR(255) DEFAULT ''         NOT NULL,
  about            TEXT                            NOT NULL,
  description      TEXT                            NOT NULL,
  link             VARCHAR(255) DEFAULT ''         NOT NULL,
  picture_url      VARCHAR(255) DEFAULT ''         NOT NULL,
  picture          INT(11) UNSIGNED                NOT NULL DEFAULT '0',
  cover_url        VARCHAR(255) DEFAULT ''         NOT NULL,
  cover            INT(11) UNSIGNED                NOT NULL DEFAULT '0',

  tstamp           INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  crdate           INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  cruser_id        INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  deleted          TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden           TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime        INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  endtime          INT(11) UNSIGNED DEFAULT '0'    NOT NULL,

  t3ver_oid        INT(11) DEFAULT '0'             NOT NULL,
  t3ver_id         INT(11) DEFAULT '0'             NOT NULL,
  t3ver_wsid       INT(11) DEFAULT '0'             NOT NULL,
  t3ver_label      VARCHAR(255) DEFAULT ''         NOT NULL,
  t3ver_state      TINYINT(4) DEFAULT '0'          NOT NULL,
  t3ver_stage      INT(11) DEFAULT '0'             NOT NULL,
  t3ver_count      INT(11) DEFAULT '0'             NOT NULL,
  t3ver_tstamp     INT(11) DEFAULT '0'             NOT NULL,
  t3ver_move_id    INT(11) DEFAULT '0'             NOT NULL,

  sys_language_uid INT(11) DEFAULT '0'             NOT NULL,
  l10n_parent      INT(11) DEFAULT '0'             NOT NULL,
  l10n_diffsource  MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_socialstream_domain_model_post'
#
CREATE TABLE tx_socialstream_domain_model_post (

  uid              INT(11)                         NOT NULL AUTO_INCREMENT,
  pid              INT(11) DEFAULT '0'             NOT NULL,

  id               VARCHAR(255) DEFAULT ''         NOT NULL,
  object_id        VARCHAR(255) DEFAULT ''         NOT NULL,
  created_time     DATETIME                                 DEFAULT '0000-00-00 00:00:00',
  link             VARCHAR(255) DEFAULT ''         NOT NULL,
  type             INT(11) DEFAULT '0'             NOT NULL,
  name             VARCHAR(255) DEFAULT ''         NOT NULL,
  caption          VARCHAR(255) DEFAULT ''         NOT NULL,
  description      TEXT                            NOT NULL,
  message          TEXT                            NOT NULL,
  story            TEXT                            NOT NULL,
  metatitle        VARCHAR(255) DEFAULT ''         NOT NULL,
  metadesc         TEXT                            NOT NULL,
  picture_url      VARCHAR(255) DEFAULT ''         NOT NULL,
  picture          INT(11) UNSIGNED                NOT NULL DEFAULT '0',
  video_url        VARCHAR(255) DEFAULT ''         NOT NULL,
  video            INT(11) UNSIGNED                NOT NULL DEFAULT '0',
  page             INT(11) UNSIGNED                         DEFAULT '0',

  tstamp           INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  crdate           INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  cruser_id        INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  deleted          TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden           TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime        INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  endtime          INT(11) UNSIGNED DEFAULT '0'    NOT NULL,

  t3ver_oid        INT(11) DEFAULT '0'             NOT NULL,
  t3ver_id         INT(11) DEFAULT '0'             NOT NULL,
  t3ver_wsid       INT(11) DEFAULT '0'             NOT NULL,
  t3ver_label      VARCHAR(255) DEFAULT ''         NOT NULL,
  t3ver_state      TINYINT(4) DEFAULT '0'          NOT NULL,
  t3ver_stage      INT(11) DEFAULT '0'             NOT NULL,
  t3ver_count      INT(11) DEFAULT '0'             NOT NULL,
  t3ver_tstamp     INT(11) DEFAULT '0'             NOT NULL,
  t3ver_move_id    INT(11) DEFAULT '0'             NOT NULL,

  sys_language_uid INT(11) DEFAULT '0'             NOT NULL,
  l10n_parent      INT(11) DEFAULT '0'             NOT NULL,
  l10n_diffsource  MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_socialstream_domain_model_gallery'
#
CREATE TABLE tx_socialstream_domain_model_gallery (

  uid              INT(11)                         NOT NULL AUTO_INCREMENT,
  pid              INT(11) DEFAULT '0'             NOT NULL,

  id               VARCHAR(255) DEFAULT ''         NOT NULL,
  picture_url      VARCHAR(255) DEFAULT ''         NOT NULL,
  picture          INT(11) UNSIGNED                NOT NULL DEFAULT '0',
  created_time     DATETIME                                 DEFAULT '0000-00-00 00:00:00',
  page             INT(11) UNSIGNED                         DEFAULT '0',
  gallery_url      VARCHAR(255) DEFAULT ''         NOT NULL,
  title            TEXT                            NOT NULL,
  description      TEXT                            NOT NULL,

  tstamp           INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  crdate           INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  cruser_id        INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  deleted          TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden           TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime        INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  endtime          INT(11) UNSIGNED DEFAULT '0'    NOT NULL,

  t3ver_oid        INT(11) DEFAULT '0'             NOT NULL,
  t3ver_id         INT(11) DEFAULT '0'             NOT NULL,
  t3ver_wsid       INT(11) DEFAULT '0'             NOT NULL,
  t3ver_label      VARCHAR(255) DEFAULT ''         NOT NULL,
  t3ver_state      TINYINT(4) DEFAULT '0'          NOT NULL,
  t3ver_stage      INT(11) DEFAULT '0'             NOT NULL,
  t3ver_count      INT(11) DEFAULT '0'             NOT NULL,
  t3ver_tstamp     INT(11) DEFAULT '0'             NOT NULL,
  t3ver_move_id    INT(11) DEFAULT '0'             NOT NULL,

  sys_language_uid INT(11) DEFAULT '0'             NOT NULL,
  l10n_parent      INT(11) DEFAULT '0'             NOT NULL,
  l10n_diffsource  MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);

#
# Table structure for table 'tx_socialstream_domain_model_event'
#
CREATE TABLE tx_socialstream_domain_model_event (

  uid              INT(11)                         NOT NULL AUTO_INCREMENT,
  pid              INT(11) DEFAULT '0'             NOT NULL,

  id               VARCHAR(255) DEFAULT ''         NOT NULL,
  start_time       DATETIME                                 DEFAULT '0000-00-00 00:00:00',
  end_time         DATETIME                                 DEFAULT '0000-00-00 00:00:00',
  name             VARCHAR(255) DEFAULT ''         NOT NULL,
  description      TEXT                            NOT NULL,
  place_name       VARCHAR(255) DEFAULT ''         NOT NULL,
  place_street     VARCHAR(255) DEFAULT ''         NOT NULL,
  place_zip        VARCHAR(255) DEFAULT ''         NOT NULL,
  place_city       VARCHAR(255) DEFAULT ''         NOT NULL,
  place_country    VARCHAR(255) DEFAULT ''         NOT NULL,
  lat              DOUBLE(13, 10) DEFAULT '0.0'    NOT NULL,
  lng              DOUBLE(13, 10) DEFAULT '0.0'    NOT NULL,
  picture_url      VARCHAR(255) DEFAULT ''         NOT NULL,
  picture          INT(11) UNSIGNED                NOT NULL DEFAULT '0',
  page             INT(11) UNSIGNED                         DEFAULT '0',

  tstamp           INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  crdate           INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  cruser_id        INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  deleted          TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  hidden           TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
  starttime        INT(11) UNSIGNED DEFAULT '0'    NOT NULL,
  endtime          INT(11) UNSIGNED DEFAULT '0'    NOT NULL,

  t3ver_oid        INT(11) DEFAULT '0'             NOT NULL,
  t3ver_id         INT(11) DEFAULT '0'             NOT NULL,
  t3ver_wsid       INT(11) DEFAULT '0'             NOT NULL,
  t3ver_label      VARCHAR(255) DEFAULT ''         NOT NULL,
  t3ver_state      TINYINT(4) DEFAULT '0'          NOT NULL,
  t3ver_stage      INT(11) DEFAULT '0'             NOT NULL,
  t3ver_count      INT(11) DEFAULT '0'             NOT NULL,
  t3ver_tstamp     INT(11) DEFAULT '0'             NOT NULL,
  t3ver_move_id    INT(11) DEFAULT '0'             NOT NULL,

  sys_language_uid INT(11) DEFAULT '0'             NOT NULL,
  l10n_parent      INT(11) DEFAULT '0'             NOT NULL,
  l10n_diffsource  MEDIUMBLOB,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY t3ver_oid (t3ver_oid, t3ver_wsid),
  KEY language (l10n_parent, sys_language_uid)

);