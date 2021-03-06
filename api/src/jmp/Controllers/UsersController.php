<?php


namespace jmp\Controllers;


use Interop\Container\ContainerInterface;
use jmp\Models\User;
use jmp\Services\Auth;
use jmp\Services\UserService;
use jmp\Utils\Converter;
use Monolog\Logger;
use Slim\Http\Request;
use Slim\Http\Response;

class UsersController
{

    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var Auth
     */
    protected $auth;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var User
     */
    private $user;

    /**
     * EventController constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->userService = $container->get('userService');
        $this->auth = $container->get('auth');
        $this->logger = $container->get('logger');
        $this->user = $container->get('user');
    }

    /**
     * Get current logged in user
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getCurrentUser(Request $request, Response $response): Response
    {
        return $response->withJson(Converter::convert($this->user));
    }

    /**
     * Returns all users
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listUsers(Request $request, Response $response): Response
    {
        $group = $request->getQueryParam('group');
        $users = $this->userService->getUsers(empty($group) ? null : $group);
        return $response->withJson(Converter::convertArray($users));
    }

    /**
     * Update data of a user
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function updateUser(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $updates = $request->getParsedBody();

        $optional = $this->userService->getUserByUserId($id);

        if ($optional->isFailure()) {
            return $response->withJson([
                'errors' => [
                    'id' => 'The specified id "' . $id . '"does not exist'
                ]
            ], 404);
        }

        /** @var User $user */
        $user = $optional->getData();

        if (isset($updates['username'])) {
            if ($this->userService->isUsernameUnique($updates['username']) === false && $updates['username'] !== $user->username) {
                return $this->usernameNotAvailable($request, $response, $updates['username']);
            }
        }

        $optional = $this->userService->updateUser($id, $updates);

        if ($optional->isFailure()) {
            $this->logger->addError('Failed to update user. ID: "' . $id . '" Updates: "' . $updates . '"');
            return $response->withStatus(500);
        }


        /** @var User $user */
        $user = $optional->getData();
        $user->password = null;
        return $response->withJson(Converter::convert($user));
    }

    /**
     * Delete a user
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function deleteUser(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];

        if ($this->userService->userExists($id) === false) {
            return $response->withJson([
                'errors' => [
                    'id' => 'The specified id "' . $id . '"does not exist'
                ]
            ], 404);
        }

        $successful = $this->userService->deleteUser($id);
        if ($successful === false) {
            $this->logger->addError('Failed to delete user: ID: "' . $id . '"');
            return $response->withStatus(500);
        }

        return $response->withStatus(204);
    }

    /**
     * Returns the user with the given id or a 404
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getUser(Request $request, Response $response, array $args): Response
    {
        $userId = $args['id'];

        $optional = $this->userService->getUserByUserId($userId);

        if ($optional->isSuccess()) {
            return $response->withJson(Converter::convert($optional->getData()));
        } else {
            return $response->withStatus(404);
        }
    }

    /**
     * Returns the user or an error
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function createUser(Request $request, Response $response): Response
    {
        $user = $request->getParsedBody();

        // check if the username is already used by an other user
        if ($this->userService->isUsernameUnique($user['username'])) {
            return $this->usernameAvailableAndCreateUser($response, $user);
        } else {
            return $this->usernameNotAvailable($request, $response, $user['username']);
        }
    }

    /**
     * Create the error response if a username is already in use
     * @param Request $request
     * @param Response $response
     * @param $username string
     * @return Response
     */
    private function usernameNotAvailable(Request $request, Response $response, string $username): Response
    {
        return $response->withJson([
            'errors' => [
                'User' => 'A user with the username ' . $username . ' already exists'
            ],
            'request' => $request->getParsedBody()
        ], 400);
    }

    /**
     * Create the response if a user can be created successfully
     * @param Response $response
     * @param $user
     * @return Response
     */
    private function usernameAvailableAndCreateUser(Response $response, $user): Response
    {
        $user['password'] = password_hash($user['password'], PASSWORD_DEFAULT);

        $optional = $this->userService->createUser(new User($user));
        if ($optional->isFailure()) {
            unset($user['password']);
            $this->logger->addError('Failed to create user. User: "' . $user . '"');
            return $response->withStatus(500);
        }

        return $response->withJson(Converter::convert($optional->getData()));
    }


    /**
     * Change tha password of a user
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function changePassword(Request $request, Response $response)
    {
        $password = $request->getParsedBodyParam('password');
        $newPassword = $request->getParsedBodyParam('newPassword');

        $passwordCheck = $this->auth->attempt($this->user->username, $password);
        if ($passwordCheck->isFailure()) {
            return $response->withJson([
                'errors' => [
                    'password' => 'The password is not correct'
                ]
            ], 400);
        }

        if ($this->userService->changePassword($this->user->id, $newPassword) === false) {
            $this->logger->addError('Failed to change password of user. User: "' . $this->user . '"');
            return $response->withStatus(500);
        }

        // password change was true but user changed the password -> set back to false
        if ($this->user->passwordChange) {
            $this->userService->updateUser($this->user->id, [
                'passwordChange' => false
            ]);
        }

        return $response->withStatus(204);

    }

}
