-- SQL Script to delete all data related to periods
-- This script deletes data in the correct order to respect foreign key constraints

-- Start transaction for safety
START TRANSACTION;

-- 1. Delete selection results (depends on interviews, evaluators, and criteria)
DELETE FROM selection_result;

-- 2. Delete interviews (depends on candidacies)
DELETE FROM interviews;

-- 3. Delete preselections (depends on period_criteria and dispatch_preselections)
DELETE FROM preselections;

-- 4. Delete dispatch preselections (depends on candidacies and evaluators)
DELETE FROM dispatch_preselections;

-- 5. Delete status historiques (depends on periods and users)
DELETE FROM status_historiques;

-- 6. Delete period_criteria (depends on periods and criteria)
DELETE FROM period_criteria;

-- 7. Delete candidacies/candidates (depends on periods)
DELETE FROM candidats;

-- 8. Delete evaluators (depends on periods and users)
DELETE FROM evaluators;

-- 9. Finally delete periods
DELETE FROM periods;

-- Commit the transaction
COMMIT;

-- Optional: Reset auto-increment counters if needed
-- ALTER TABLE selection_result AUTO_INCREMENT = 1;
-- ALTER TABLE interviews AUTO_INCREMENT = 1;
-- ALTER TABLE preselections AUTO_INCREMENT = 1;
-- ALTER TABLE dispatch_preselections AUTO_INCREMENT = 1;
-- ALTER TABLE status_historiques AUTO_INCREMENT = 1;
-- ALTER TABLE period_criteria AUTO_INCREMENT = 1;
-- ALTER TABLE candidats AUTO_INCREMENT = 1;
-- ALTER TABLE evaluators AUTO_INCREMENT = 1;
-- ALTER TABLE periods AUTO_INCREMENT = 1;
