<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
         $user = new User();
         $user->setEmail('test@test.com');
         $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
         $user->setIsVerified(true);
         $user->setPassword($this->passwordEncoder->encodePassword($user, 'test'));
         $manager->persist($user);


         $user2 = new User();
         $user2->setEmail('test2@test.com');
         $user2->setRoles(['ROLE_USER']);
         $user2->setIsVerified(true);
         $user2->setPassword($this->passwordEncoder->encodePassword($user2, 'test2'));
         $manager->persist($user2);

        $manager->flush();
    }
}
