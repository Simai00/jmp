<?php

namespace jmp\Services;


use jmp\Models\RegistrationState;
use jmp\Utils\Optional;
use PDO;
use Psr\Container\ContainerInterface;

class RegistrationStateService
{
    /**
     * @var PDO
     */
    protected $db;

    /**
     * EventService constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->db = $container->get('database');
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteRegistrationState(int $id): bool
    {
        $sql = <<<SQL
DELETE FROM jmp.registration_state
WHERE jmp.registration_state.id = :id
SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * @param RegistrationState $registrationState
     * @return Optional
     */
    public function createRegistrationState(RegistrationState $registrationState): Optional
    {
        $sql = <<<SQL
INSERT INTO jmp.registration_state
(jmp.registration_state.name, jmp.registration_state.reason_required) 
VALUES (:name, :reasonRequired)
SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $registrationState->name);
        $stmt->bindParam(':reasonRequired', $registrationState->reasonRequired);

        if ($stmt->execute() === false) {
            return Optional::failure();
        }

        $registrationStateId = $this->getLastInsertedEventTypeId();
        if ($registrationStateId === false) {
            return Optional::failure();
        }

        $registrationStateId = $registrationStateId['id'];

        return $this->getRegistrationStateById($registrationStateId);
    }

    /**
     * @return mixed
     */
    private function getLastInsertedEventTypeId()
    {
        $sql = <<< SQL
SELECT LAST_INSERT_ID() as id;
SQL;

        $stmt = $this->db->prepare($sql);
        // Gets the ID of the inserted event
        $stmt->execute();

        $eventId = $stmt->fetch();
        return $eventId;
    }

    /**
     * @return Optional
     */
    public function getAllRegStates()
    {
        $sql = <<< SQL
SELECT id, name, reason_required as reasonRequired
FROM registration_state
SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $registrationStates = $stmt->fetchAll();

        if ($registrationStates === false) {
            return Optional::failure();
        }

        foreach ($registrationStates as $key => $val) {
            $registrationStates[$key] = new RegistrationState($val);
        }

        return Optional::success($registrationStates);
    }

    /**
     * @param int $registrationStateId
     * @return Optional
     */
    public function getRegistrationStateById(int $registrationStateId)
    {
        $sql = <<< SQL
SELECT id, name, reason_required as reasonRequired
FROM registration_state
WHERE id = :registrationStateId
SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':registrationStateId', $registrationStateId);
        $stmt->execute();
        $registrationState = $stmt->fetch();
        if ($registrationState === false) {
            return Optional::failure();
        } else {
            return Optional::success(new RegistrationState($registrationState));
        }
    }


    /**
     * Checks whether a registration state with the given id already exists
     * @param int $registrationStateId
     * @return bool
     */
    public function registrationStateExists(int $registrationStateId): bool
    {
        $sql = <<<SQL
            SELECT id
            FROM jmp.`registration_state`
            WHERE id = :id
SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $registrationStateId);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}