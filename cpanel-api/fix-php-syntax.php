<?php
$content = file_get_contents('users-frotas-api.php.bak');

// Replace all ?? operators
$replacements = [
    "\$action = \$_GET['action'] ?? '';" => "\$action = isset(\$_GET['action']) ? \$_GET['action'] : '';",
    "\$username = \$data['username'] ?? '';" => "\$username = isset(\$data['username']) ? \$data['username'] : '';",
    "\$password = \$data['password'] ?? '';" => "\$password = isset(\$data['password']) ? \$data['password'] : '';",
    "\$userId = \$data['user_id'] ?? 0;" => "\$userId = isset(\$data['user_id']) ? intval(\$data['user_id']) : 0;",
    "\$newPassword = \$data['new_password'] ?? '';" => "\$newPassword = isset(\$data['new_password']) ? \$data['new_password'] : '';",
    "\$fullName = \$data['full_name'] ?? '';" => "\$fullName = isset(\$data['full_name']) ? \$data['full_name'] : '';",
    "\$email = \$data['email'] ?? null;" => "\$email = isset(\$data['email']) ? \$data['email'] : null;",
    "\$password = \$data['password'] ?? null;" => "\$password = isset(\$data['password']) ? \$data['password'] : null;",
    "\$userType = \$data['user_type'] ?? 'usuario';" => "\$userType = isset(\$data['user_type']) ? \$data['user_type'] : 'usuario';",
    "\$userId = \$_GET['user_id'] ?? 0;" => "\$userId = isset(\$_GET['user_id']) ? intval(\$_GET['user_id']) : 0;",
    "\$userId = \$_GET['id'] ?? 0;" => "\$userId = isset(\$_GET['id']) ? intval(\$_GET['id']) : 0;",
];

foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

file_put_contents('users-frotas-api.php', $content);
echo "Fixed!\n";
