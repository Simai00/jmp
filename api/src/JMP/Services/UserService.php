<?php


namespace JMP\Services;


use JMP\Models\User;
use JMP\Utils\Optional;
use PDO;
use Psr\Container\ContainerInterface;

class UserService
{
    /**
     * @var \PDO
     */
    protected $db;

    /**
     * EventService constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->db = $container->get('database');
    }

    /**
     * Creates a new user and returns the created one by @uses UserService::getUserByUsername()
     * The given user is saved as is. E.g password hashing must be done in advance
     * @param User $user
     * @return User
     */
    public function createUser(User $user): User
    {
        $sql = <<<SQL
INSERT INTO user
(username, lastname, firstname, email, password, password_change, is_admin) 
VALUES (:username, :lastname, :firstname, :email, :password, :password_change, :is_admin)
SQL;

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':username', $user->username);
        $stmt->bindValue(':lastname', $user->lastname, \PDO::PARAM_STR);
        $stmt->bindValue(':firstname', $user->firstname, \PDO::PARAM_STR);
        $stmt->bindValue(':email', $user->email, \PDO::PARAM_STR);
        $stmt->bindParam(':password', $user->password);
        $stmt->bindValue(':password_change', 1, \PDO::PARAM_INT);
        $stmt->bindValue(':is_admin', $user->isAdmin, \PDO::PARAM_INT);


        $stmt->execute();

        return $this->getUserByUsername($user->username);

    }

    /**
     * Checks whether a user with the given username already exists, as it must be unique
     * @param string $username
     * @return bool
     */
    public function isUsernameUnique(string $username): bool
    {
        $sql = <<<SQL
SELECT user.username
FROM user
WHERE username = :username
SQL;

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':username', $username);

        $stmt->execute();

        return $stmt->rowCount() < 1;

    }

    /**
     * Returns the user with the given username.
     * The password isn't returned.
     * Null fields are returned
     * @param $username
     * @return User
     */
    private function getUserByUsername(string $username): User
    {
        $sql = <<<SQL
SELECT user.id, username, lastname, firstname, email, is_admin AS isAdmin
FROM user
WHERE username = :username
SQL;


        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':username', $username);

        $stmt->execute();

        return new User($stmt->fetch());
    }

    /**
     * @param int|null $groupId
     * @return User[]
     */
    public function getUsers(?int $groupId): array
    {
        $sql = <<< SQL
            SELECT DISTINCT user.id, username, lastname, firstname, email, is_admin
            FROM user
                LEFT JOIN membership m on user.id = m.user_id
            WHERE (:groupId IS NULL OR m.group_id = :groupId)
SQL;

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':groupId', $groupId, PDO::PARAM_INT);
        $stmt->execute();

        $users = $stmt->fetchAll();

        foreach ($users as $key => $val) {
            $users[$key] = new User($val);
        }

        return $users;
    }

    /**
     * Change the password of a user, fails if the current password is incorrect
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = <<< SQL
            UPDATE user
            SET password = :newPassword
            WHERE id = :userId
SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':newPassword', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

}