<?php
require_once __DIR__ . '/../helpers.php';

class User
{
    protected static function pdo()
    {
        return getPDO();
    }

    public static function create($data)
    {
        $pdo = self::pdo();
        $sql = "INSERT INTO users (role,name,email,phone,password_hash,is_verified) VALUES (?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $data['role'] ?? 'jobseeker',
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['password_hash'],
            $data['is_verified'] ?? 0
        ]);

        if ($result) {
            return $pdo->lastInsertId();
        }
        return false;
    }

    public static function findByEmail($email)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function findById($id)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function update($id, $fields)
    {
        $pdo = self::pdo();
        $set = [];
        $params = [];
        foreach ($fields as $k => $v) {
            $set[] = "$k = ?";
            $params[] = $v;
        }
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(',', $set) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function verifyPassword($email, $password)
    {
        $user = self::findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }
}
