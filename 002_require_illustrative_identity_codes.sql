ALTER TABLE households
    ADD CONSTRAINT chk_households_demo_number
        CHECK (household_number REGEXP '^KKDEMO[0-9]{10}$'),
    ADD CONSTRAINT chk_households_demo_head
        CHECK (head_nik IS NULL OR head_nik REGEXP '^DEMO[0-9]{12}$');

ALTER TABLE residents
    ADD CONSTRAINT chk_residents_demo_code
        CHECK (nik REGEXP '^DEMO[0-9]{12}$');

ALTER TABLE population_events
    ADD CONSTRAINT chk_events_demo_code
        CHECK (nik IS NULL OR nik REGEXP '^DEMO[0-9]{12}$');
