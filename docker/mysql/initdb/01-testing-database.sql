-- The database the test suite migrates from scratch (phpunit.xml sets DB_DATABASE=eletrontech_testing),
-- so a test run never wipes the data of the running app. MySQL runs this once, when its volume is new.
CREATE DATABASE IF NOT EXISTS eletrontech_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON eletrontech_testing.* TO 'eletrontech'@'%';
