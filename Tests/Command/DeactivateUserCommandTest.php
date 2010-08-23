<?php

namespace Bundle\DoctrineUserBundle\Tests\Command;

use Bundle\DoctrineUserBundle\Tests\BaseDatabaseTest;
use Bundle\DoctrineUserBundle\DAO\User;
use Bundle\DoctrineUserBundle\Command\DeactivateUserCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Components\Console\Output\Output;
use Symfony\Components\Console\Tester\ApplicationTester;

// Kernel creation required namespaces
use Symfony\Components\Finder\Finder;

class DeactivateUserCommandTest extends BaseDatabaseTest
{
    public function testUserDeactivation()
    {
        $kernel = self::createKernel();
        $command = new DeactivateUserCommand();
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);

        $username = 'test_username';
        $password = 'test_password';
        $email    = 'test_email@email.org';

        $userRepo = $kernel->getContainer()->get('doctrine_user.user_repository');
        $userClass = $userRepo->getObjectClass();

        $user = new $userClass();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($password);

        $userRepo->getObjectManager()->persist($user);
        $userRepo->getObjectManager()->flush();
        
        $this->assertTrue($user->getIsActive());

        $tester->run(array(
            'command'  => $command->getFullName(),
            'username' => $username,
        ), array('interactive' => false, 'decorated' => false, 'verbosity' => Output::VERBOSITY_VERBOSE));

        $kernel = self::createKernel();
        $userRepo = $kernel->getContainer()->get('doctrine_user.user_repository');
        $user = $userRepo->findOneByUsername($username);

        $this->assertTrue($user instanceof User);
        $this->assertFalse($user->getIsActive());

        $userRepo->getObjectManager()->remove($user);
        $userRepo->getObjectManager()->flush();
    }


    static public function tearDownAfterClass()
    {
        $userRepo = self::createKernel()->getContainer()->getDoctrineUser_UserRepositoryService();
        $objectManager = $userRepo->getObjectManager();
        if($object = $userRepo->findOneByUsername('test_username')) {
            $objectManager->remove($object);
        }
        $objectManager->flush();
    }
}
