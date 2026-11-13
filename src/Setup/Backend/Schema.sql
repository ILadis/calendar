CREATE TABLE IF NOT EXISTS "registrations" (
  "id" INTEGER PRIMARY KEY ASC NOT NULL,
  "username" TEXT NOT NULL,
  "completed" TEXT,
  UNIQUE("username")
);

CREATE TABLE IF NOT EXISTS "users" (
  "id" INTEGER PRIMARY KEY ASC NOT NULL,
  "username" TEXT NOT NULL,
  "password" TEXT NOT NULL,
  UNIQUE("username")
);

CREATE TABLE IF NOT EXISTS "calendars" (
  "id" INTEGER PRIMARY KEY ASC NOT NULL,
  "synctoken" INTEGER DEFAULT 1 NOT NULL,
  "components" TEXT COMMENT 'VJOURNAL, VTODO, VEVENT' NOT NULL
);

CREATE TABLE IF NOT EXISTS "calendarinstances" (
  "id" INTEGER PRIMARY KEY ASC NOT NULL,
  "calendarid" INTEGER NOT NULL,
  "principaluri" TEXT NOT NULL,
  "access" INTEGER COMMENT '1 = owner, 2 = read, 3 = readwrite' NOT NULL DEFAULT '1',
  "displayname" TEXT,
  "uri" TEXT NOT NULL,
  "description" TEXT,
  "calendarorder" INTEGER,
  "calendarcolor" TEXT,
  "timezone" TEXT,
  "transparent" BOOLEAN DEFAULT '0',
  "share_href" TEXT,
  "share_displayname" TEXT,
  "share_invitestatus" INTEGER DEFAULT '2',
  UNIQUE("principaluri", "uri"),
  UNIQUE("calendarid", "principaluri"),
  UNIQUE("calendarid", "share_href")
);

CREATE INDEX IF NOT EXISTS "principaluri_calendarorder" ON "calendarinstances" ("principaluri", "calendarorder");

CREATE TABLE IF NOT EXISTS "calendarobjects" (
  "id" INTEGER PRIMARY KEY ASC NOT NULL,
  "calendardata" BLOB NOT NULL,
  "uri" TEXT NOT NULL,
  "calendarid" INTEGER NOT NULL,
  "lastmodified" INTEGER NOT NULL,
  "etag" TEXT NOT NULL,
  "size" INTEGER NOT NULL,
  "componenttype" TEXT,
  "firstoccurence" INTEGER,
  "lastoccurence" INTEGER,
  "uid" TEXT,
  UNIQUE("calendarid", "uri")
);

CREATE INDEX IF NOT EXISTS "calendarid_time" ON "calendarobjects" ("calendarid", "firstoccurence");
CREATE INDEX IF NOT EXISTS "calendarid_component_firstoccurence" ON "calendarobjects" ("calendarid", "componenttype", "firstoccurence");
CREATE INDEX IF NOT EXISTS "calendarid_lastoccurence" ON "calendarobjects" ("calendarid", "lastoccurence");
CREATE INDEX IF NOT EXISTS "calendarid_uid" ON "calendarobjects" ("calendarid", "uid");

CREATE TABLE IF NOT EXISTS "calendarchanges" (
  "id" INTEGER PRIMARY KEY ASC NOT NULL,
  "uri" TEXT,
  "synctoken" INTEGER NOT NULL,
  "calendarid" INTEGER NOT NULL,
  "operation" INTEGER COMMENT '1 = add, 2 = modify, 3 = delete' NOT NULL
);

CREATE INDEX IF NOT EXISTS "calendarid_synctoken" ON "calendarchanges" ("calendarid", "synctoken");

CREATE TABLE IF NOT EXISTS "calendarsubscriptions" (
  "id" INTEGER PRIMARY KEY ASC NOT NULL,
  "uri" TEXT NOT NULL,
  "principaluri" TEXT NOT NULL,
  "source" TEXT NOT NULL,
  "displayname" TEXT,
  "refreshrate" TEXT,
  "calendarorder" INTEGER,
  "calendarcolor" TEXT,
  "striptodos" BOOLEAN,
  "stripalarms" BOOLEAN,
  "stripattachments" BOOLEAN,
  "lastmodified" INTEGER,
  UNIQUE("principaluri", "uri")
);

CREATE INDEX IF NOT EXISTS "subscriptions_principaluri_calendarorder" ON "calendarsubscriptions" ("principaluri", "calendarorder");

CREATE TABLE IF NOT EXISTS "schedulingobjects" (
  "id" INTEGER PRIMARY KEY ASC NOT NULL,
  "principaluri" TEXT NOT NULL,
  "calendardata" BLOB,
  "uri" TEXT NOT NULL,
  "lastmodified" INTEGER,
  "etag" TEXT NOT NULL,
  "size" INTEGER NOT NULL,
  UNIQUE("principaluri", "uri")
);

CREATE TABLE IF NOT EXISTS "principals" (
  "id" INTEGER PRIMARY KEY ASC NOT NULL,
  "uri" TEXT NOT NULL,
  "email" TEXT,
  "displayname" TEXT,
  UNIQUE("uri")
);

CREATE INDEX IF NOT EXISTS "principals_email_lower" ON "principals" (LOWER("email"));

CREATE TABLE IF NOT EXISTS groupmembers (
  "id" INTEGER PRIMARY KEY ASC NOT NULL,
  "principal_id" INTEGER NOT NULL,
  "member_id" INTEGER NOT NULL,
  UNIQUE(principal_id, member_id)
);

CREATE INDEX IF NOT EXISTS "groupmembers_member_id" ON "groupmembers" ("member_id");

CREATE TABLE IF NOT EXISTS "propertystorage" (
  "id" INTEGER PRIMARY KEY ASC NOT NULL,
  "path" TEXT NOT NULL,
  "name" TEXT NOT NULL,
  "valuetype" INTEGER NOT NULL,
  "value" TEXT
);

CREATE UNIQUE INDEX IF NOT EXISTS "path_property" ON "propertystorage" ("path", "name");
