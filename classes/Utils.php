<?php
include_once(dirname(__DIR__) . '/config/conn.php'); 

if (!class_exists('Utils')) {
    class Utils {
        // Get user's role permissions as an array
        public static function getUserPermissions($conn, $user_id) {
            // Get c_id from session
            // session_start();
            $c_id = isset($_SESSION['c_id']) ? $_SESSION['c_id'] : null;
            if (!$c_id) return [];

            // Fetch role_id for user with c_id
            $role_id = null;
            $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ? AND c_id = ?");
            if (!$stmt) return [];
            $stmt->bind_param("ii", $user_id, $c_id);
            $stmt->execute();
            $stmt->bind_result($role_id);
            $stmt->fetch();
            $stmt->close();
            if (!$role_id) return [];

            // Always fetch permissions for role from DB for all roles with c_id
            $permissions_json = null;
            $stmt2 = $conn->prepare("SELECT permissions FROM roles WHERE id = ? AND c_id = ?");
            if (!$stmt2) return [];
            $stmt2->bind_param("ii", $role_id, $c_id);
            $stmt2->execute();
            $stmt2->bind_result($permissions_json);
            $stmt2->fetch();
            $stmt2->close();
            if (!$permissions_json) return [];

            $permissions = json_decode($permissions_json, true);
            if (!is_array($permissions)) $permissions = [];
            return $permissions;
        }

        // Generic permission check
        public static function hasPermission($conn, $user_id, $permission) {
            $perms = self::getUserPermissions($conn, $user_id);
            return is_array($perms) && in_array($permission, $perms);
        }

        // Specific permission checks
        public static function canUploadDocuments($conn, $user_id) {
            return self::hasPermission($conn, $user_id, 'uploadDocuments');
        }
        public static function canDownloadDocuments($conn, $user_id) {
            return self::hasPermission($conn, $user_id, 'downloadDocuments');
        }
        public static function canCommentDocuments($conn, $user_id) {
            return self::hasPermission($conn, $user_id, 'commentDocuments');
        }
        public static function canShareDocuments($conn, $user_id) {
            return self::hasPermission($conn, $user_id, 'shareDocuments');
        }
        public static function canManageDocuments($conn, $user_id) {
            return self::hasPermission($conn, $user_id, 'manageDocuments');
        }
        public static function canManageFolders($conn, $user_id) {
            return self::hasPermission($conn, $user_id, 'manageFolders');
        }
        public static function canManageTags($conn, $user_id) {
            return self::hasPermission($conn, $user_id, 'manageTags');
        }
        public static function canDeleteDocuments($conn, $user_id) {
            return self::hasPermission($conn, $user_id, 'deleteDocuments');
        }

        // Check if user is Super Admin (role_id = 1)
        public static function isSuperadmin($conn, $user_id) {
            $c_id = isset($_SESSION['c_id']) ? $_SESSION['c_id'] : null;
            if (!$c_id) return false;
            $role_id = null;
            $stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ? AND c_id = ?");
            if (!$stmt) return false;
            $stmt->bind_param("ii", $user_id, $c_id);
            $stmt->execute();
            $stmt->bind_result($role_id);
            $stmt->fetch();
            $stmt->close();
            return $role_id == 1;
        }

        // Log activity to activity_logs table 
        public static function logActivity($conn, $user_id, $action, $entity_type, $entity_id, $description = null) {
            $c_id = isset($_SESSION['c_id']) ? $_SESSION['c_id'] : null;
            if (!$c_id) return false;
            $ip_addr = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, c_id, action, entity_type, entity_id, description, ip_addr, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            if (!$stmt) return false;
            $stmt->bind_param("iississ", $user_id, $c_id, $action, $entity_type, $entity_id, $description, $ip_addr);
            return $stmt->execute();
        }
    }
}