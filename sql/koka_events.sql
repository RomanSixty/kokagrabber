CREATE TABLE IF NOT EXISTS koka_events (
	id INTEGER PRIMARY KEY,
	artist TEXT,
	link TEXT,
	createdate INTEGER DEFAULT 0,
	viewdate INTEGER DEFAULT 0,
	lastseendate INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS koka_settings (
	id INTEGER PRIMARY KEY,
	skey TEXT,
	sval TEXT
);

CREATE UNIQUE INDEX IF NOT EXISTS setting ON koka_settings (skey);

ALTER TABLE "koka_events"
	ADD COLUMN "eventdate" TEXT NULL;