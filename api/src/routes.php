<?php

use DavidePastore\Slim\Validation\Validation;
use jmp\Controllers\EventsController;
use jmp\Controllers\EventTypesController;
use jmp\Controllers\GroupsController;
use jmp\Controllers\LoginController;
use jmp\Controllers\PresenceController;
use jmp\Controllers\PresencesController;
use jmp\Controllers\RegistrationController;
use jmp\Controllers\RegistrationsController;
use jmp\Controllers\RegistrationStateController;
use jmp\Controllers\UsersController;
use jmp\Middleware\AuthenticationMiddleware;
use jmp\Middleware\CORSMiddleware;
use jmp\Middleware\ValidationErrorResponseBuilder;
use jmp\Utils\PermissionLevel;
use Psr\Container\ContainerInterface;
use Tuupola\Middleware\JwtAuthentication;

/** @var $app Slim\App */
/** @var ContainerInterface $container */
/** @var $jwtMiddleware JwtAuthentication */

$container = $app->getContainer();
$jwtMiddleware = $container['jwt'];

// CORS
$app->add(new CORSMiddleware($container['settings']['cors']));

// API Routes - version 1
$app->group('/v1', function () use ($container, $jwtMiddleware) {
    /** @var $this Slim\App */

    // Login
    $this->group('/login', function () use ($container, $jwtMiddleware) {
        /** @var $this Slim\App */

        $this->post('', LoginController::class . ':login')
            ->add(new ValidationErrorResponseBuilder())
            ->add(new Validation(
                $container['validation']['login'],
                $container['validation']['loginTranslation']
            ))
            ->add(new AuthenticationMiddleware($container, PermissionLevel::OPEN));
    });

    // Events
    $this->group('/events', function () use ($container, $jwtMiddleware) {
        /** @var $this Slim\App */

        $this->get('', EventsController::class . ':listEvents')
            ->add(new ValidationErrorResponseBuilder())
            ->add(new Validation($container['validation']['listEvents']))
            ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
            ->add($jwtMiddleware);

        $this->post('', EventsController::class . ':createEvent')
            ->add(new ValidationErrorResponseBuilder())
            ->add(new Validation($container['validation']['createEvent']))
            ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
            ->add($jwtMiddleware);

        $this->group('/{id:[0-9]+}', function () use ($container, $jwtMiddleware) {
            /** @var $this Slim\App */

            $this->get('', EventsController::class . ':getEventById')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
                ->add($jwtMiddleware);

            $this->put('', EventsController::class . ':updateEvent')
                ->add(new ValidationErrorResponseBuilder())
                ->add(new Validation($container['validation']['updateEvent']))
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);

            $this->delete('', EventsController::class . ':deleteEvent')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);

            $this->get('/registrations', RegistrationsController::class . ':getRegistrations')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);

            $this->group('/presences', function () use ($container, $jwtMiddleware) {
                /** @var $this Slim\App */

                $this->get('', PresencesController::class . ':getPresences')
                    ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                    ->add($jwtMiddleware);

                $this->post('', PresencesController::class . ':createPresences')
                    ->add(new ValidationErrorResponseBuilder())
                    ->add(new Validation($container['validation']['createPresences']))
                    ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                    ->add($jwtMiddleware);

                $this->put('', PresencesController::class . ':updatePresences')
                    ->add(new ValidationErrorResponseBuilder())
                    ->add(new Validation($container['validation']['updatePresences']))
                    ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                    ->add($jwtMiddleware);

                $this->delete('', PresencesController::class . ':deletePresences')
                    ->add(new ValidationErrorResponseBuilder())
                    ->add(new Validation($container['validation']['deletePresences']))
                    ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                    ->add($jwtMiddleware);
            });
        });
    });

    // Registration
    $this->group('/registration', function () use ($container, $jwtMiddleware) {
        /** @var $this Slim\App */

        $this->post('', RegistrationController::class . ':createRegistration')
            ->add(new ValidationErrorResponseBuilder())
            ->add(new Validation($this->getContainer()['validation']['createRegistration']))
            ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
            ->add($jwtMiddleware);

        $this->group('/{eventId:[0-9]+}/{userId:[0-9]+}', function () use ($container, $jwtMiddleware) {
            /** @var $this Slim\App */

            $this->get('', RegistrationController::class . ':getRegistrationByEventIdAndUserId')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
                ->add($jwtMiddleware);

            $this->put('', RegistrationController::class . ':updateRegistration')
                ->add(new ValidationErrorResponseBuilder())
                ->add(new Validation($this->getContainer()['validation']['updateRegistration']))
                ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
                ->add($jwtMiddleware);

            $this->delete('', RegistrationController::class . ':deleteRegistration')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
                ->add($jwtMiddleware);

        });
    });

    // Registration-State
    $this->group('/registration-state', function () use ($container, $jwtMiddleware) {
        /** @var $this Slim\App */

        $this->get('', RegistrationStateController::class . ':getAllRegStates')
            ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
            ->add($jwtMiddleware);

        $this->post('', RegistrationStateController::class . ':createRegistrationState')
            ->add(new ValidationErrorResponseBuilder())
            ->add(new Validation($this->getContainer()['validation']['createRegistrationState']))
            ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
            ->add($jwtMiddleware);

        $this->group('/{id:[0-9]+}', function () use ($container, $jwtMiddleware) {
            /** @var $this Slim\App */

            $this->put('', RegistrationStateController::class . ':updateRegistrationState')
                ->add(new ValidationErrorResponseBuilder())
                ->add(new Validation($this->getContainer()['validation']['updateRegistrationState']))
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);

            $this->get('', RegistrationStateController::class . ':getRegistrationStateById')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
                ->add($jwtMiddleware);


            $this->delete('', RegistrationStateController::class . ':deleteRegistrationState')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);
        });
    });

    // User
    $this->group('/user', function () use ($container, $jwtMiddleware) {
        /** @var $this Slim\App */

        $this->get('', UsersController::class . ':getCurrentUser')
            ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
            ->add($jwtMiddleware);

        $this->put('/change-password', UsersController::class . ':changePassword')
            ->add(new ValidationErrorResponseBuilder())
            ->add(new Validation(
                $container['validation']['changePassword'],
                $container['validation']['loginTranslation']))
            ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
            ->add($jwtMiddleware);
    });

    // Users
    $this->group('/users', function () use ($container, $jwtMiddleware) {
        /** @var $this Slim\App */

        $this->post('', UsersController::class . ':createUser')
            ->add(new ValidationErrorResponseBuilder())
            ->add(new Validation($container['validation']['createUser']))
            ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
            ->add($jwtMiddleware);

        $this->get('', UsersController::class . ':listUsers')
            ->add(new ValidationErrorResponseBuilder())
            ->add(new Validation($container['validation']['listUsers']))
            ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
            ->add($jwtMiddleware);

        $this->group('/{id:[0-9]+}', function () use ($container, $jwtMiddleware) {
            /** @var $this Slim\App */

            $this->put('', UsersController::class . ':updateUser')
                ->add(new ValidationErrorResponseBuilder())
                ->add(new Validation($container['validation']['updateUser']))
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);

            $this->delete('', UsersController::class . ':deleteUser')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);

            $this->get('', UsersController::class . ':getUser')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);
        });
    });

    // Groups
    $this->group('/groups', function () use ($container, $jwtMiddleware) {
        /** @var $this Slim\App */

        $this->post('', GroupsController::class . ':createGroup')
            ->add(new ValidationErrorResponseBuilder())
            ->add(new Validation($container['validation']['createGroup']))
            ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
            ->add($jwtMiddleware);

        $this->get('', GroupsController::class . ':listGroups')
            ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
            ->add($jwtMiddleware);

        $this->group('/{id:[0-9]+}', function () use ($container, $jwtMiddleware) {
            /** @var $this Slim\App */

            $this->get('', GroupsController::class . ':getGroupById')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
                ->add($jwtMiddleware);

            $this->put('', GroupsController::class . ':updateGroup')
                ->add(new ValidationErrorResponseBuilder())
                ->add(new Validation($container['validation']['updateGroup']))
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);

            $this->delete('', GroupsController::class . ':deleteGroup')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);

            $this->post('/join', GroupsController::class . ':joinGroup')
                ->add(new ValidationErrorResponseBuilder())
                ->add(new Validation($container['validation']['userIdsArray']))
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);

            $this->delete('/leave', GroupsController::class . ':leaveGroup')
                ->add(new ValidationErrorResponseBuilder())
                ->add(new Validation($container['validation']['userIdsArray']))
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);
        });
    });

    $this->group('/event-types', function () use ($container, $jwtMiddleware) {
        /** @var $this Slim\App */

        $this->post('', EventTypesController::class . ':createEventType')
            ->add(new ValidationErrorResponseBuilder())
            ->add(new Validation($container['validation']['createEventType']))
            ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
            ->add($jwtMiddleware);

        $this->get('', EventTypesController::class . ':listEventTypes')
            ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
            ->add($jwtMiddleware);

        $this->group('/{id:[0-9]+}', function () use ($container, $jwtMiddleware) {
            /** @var $this Slim\App */

            $this->get('', EventTypesController::class . ':getEventTypeById')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::USER))
                ->add($jwtMiddleware);

            $this->put('', EventTypesController::class . ':updateEventType')
                ->add(new ValidationErrorResponseBuilder())
                ->add(new Validation($container['validation']['updateEventType']))
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);

            $this->delete('', EventTypesController::class . ':deleteEventType')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);
        });
    });

    $this->group('/presence', function () use ($container, $jwtMiddleware) {
        /** @var $this Slim\App */

        $this->post('', PresenceController::class . ':createPresence')
            ->add(new ValidationErrorResponseBuilder())
            ->add(new Validation($container['validation']['createPresence']))
            ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
            ->add($jwtMiddleware);

        $this->group('/{eventId:[0-9]+}/{userId:[0-9]+}/{auditorId:[0-9]+}', function () use ($container, $jwtMiddleware) {
            /** @var $this Slim\App */

            $this->put('', PresenceController::class . ':updatePresence')
                ->add(new ValidationErrorResponseBuilder())
                ->add(new Validation($container['validation']['updatePresence']))
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);

            $this->delete('', PresenceController::class . ':deletePresence')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);

            $this->get('', PresenceController::class . ':getPresence')
                ->add(new AuthenticationMiddleware($container, PermissionLevel::ADMIN))
                ->add($jwtMiddleware);
        });
    });
});
