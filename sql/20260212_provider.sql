ALTER TABLE "koka_events"
    ADD COLUMN "provider" VARCHAR(50);

UPDATE "koka_events"
    SET provider = "koka36"
    WHERE link LIKE "%koka36%";

UPDATE "koka_events"
    SET provider = "eventim"
    WHERE link LIKE "%eventim%";