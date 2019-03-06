<?php


namespace JMP\Middleware;


use JMP\Services\Auth;
use JMP\Utils\PermissionLevel;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthenticationMiddleware
{
    /**
     * @var Auth
     */
    private $auth;
    /**
     * @var int
     */
    private $permissionLevel;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * AuthenticationMiddleware constructor.
     * @param ContainerInterface $container
     * @param int $permissionLevel
     */
    public function __construct(ContainerInterface $container, int $permissionLevel)
    {
        $this->auth = $container->get('auth');
        $this->permissionLevel = $permissionLevel;
        $this->logger = $container->get('logger');
    }


    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $next): Response
    {
        switch ($this->permissionLevel) {
            case PermissionLevel::OPEN:
                {
                    return $next($request, $response);
                }
            case PermissionLevel::USER:
                {
                    return $this->user($request, $response, $next);
                }
            case PermissionLevel::ADMIN:
                {
                    return $this->admin($request, $response, $next);
                }
            default:
                {
                    return $this->invalidPermissionLevel($request, $response);
                }
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    private function user(Request $request, Response $response, callable $next): Response
    {
        if ($this->auth->requestUser($request)->isFailure()) {
            return $response->withStatus(401);
        }
        return $next($request, $response);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    private function admin(Request $request, Response $response, callable $next): Response
    {
// Check user for admin permissions
        if ($this->auth->requestAdmin($request)->isFailure()) {
            if ($request->getAttribute('token')) {
                // Token supplied, but no admin permissions
                return $response->withStatus(403);
            } else {
                // No token supplied
                return $response->withStatus(401);
            }
        }
        return $next($request, $response);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    private function invalidPermissionLevel(Request $request, Response $response): Response
    {
        $this->logger->addError('An invalid permission level was tried to be used. PermissionLevel: "' . $this->permissionLevel . '" Route: "' . $request->getMethod() . ':' . $request->getUri() . '"');
        return $response->withStatus(500)->withJson(
            [
                'errors' => [
                    'internalServerError' => 'An unexpected error occurred'
                ]
            ]
        );
    }

}
