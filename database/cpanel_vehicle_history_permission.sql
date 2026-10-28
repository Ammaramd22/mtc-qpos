-- QPOS — Vehicle History permission (NO TERMINAL)
-- phpMyAdmin → SQL → Go
-- Safe if permission already exists.

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'vehicle_modification.view_history', 'web', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `permissions` WHERE `name` = 'vehicle_modification.view_history'
);

-- Grant to Admin#* roles
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
CROSS JOIN `roles` r
WHERE p.name = 'vehicle_modification.view_history'
  AND r.name LIKE 'Admin#%'
  AND NOT EXISTS (
    SELECT 1 FROM `role_has_permissions` rh
    WHERE rh.permission_id = p.id AND rh.role_id = r.id
  );
