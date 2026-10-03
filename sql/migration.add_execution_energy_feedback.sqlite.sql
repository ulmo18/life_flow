-- Apply only when the base Execution Energy migration was deployed before burden_feedback was added.
ALTER TABLE execution_energy_days ADD COLUMN burden_feedback TEXT NULL;
