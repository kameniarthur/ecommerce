<?php
// app/models/User.php

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['email', 'password', 'first_name', 'last_name'];

    // --- Authentification ---
    public function register($data)
    {
        $data['password'] = $this->hashPassword($data['password']);
        return $this->create($data);
    }

    public function login($email, $password)
    {
        $user = $this->findByEmail($email);
        if ($user && $this->verifyPassword($password, $user['password'])) {
            Session::setUser($user);
            return true;
        }
        return false;
    }

    public function findByEmail($email)
    {
        return $this->findBy('email', $email);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function updatePassword($userId, $newPassword)
    {
        return $this->update($userId, ['password' => $this->hashPassword($newPassword)]);
    }

    // --- Profil ---
    public function updateProfile($userId, $data)
    {
        $data = array_intersect_key($data, array_flip(['first_name', 'last_name', 'email']));
        return $this->update($userId, $data);
    }

    public function getAddresses($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM addresses WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function addAddress($userId, $address)
    {
        $address['user_id'] = $userId;
        $columns = implode(',', array_keys($address));
        $values = ':' . implode(', :', array_keys($address));
        $sql = "INSERT INTO addresses ($columns) VALUES ($values)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($address);
    }

    // --- Rôles ---
    public function isAdmin($userId)
    {
        $user = $this->find($userId);
        return $user && $user['role'] === 'admin';
    }

    public function setRole($userId, $role)
    {
        return $this->update($userId, ['role' => $role]);
    }
}
