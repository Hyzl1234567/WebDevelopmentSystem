<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

class UserFixtures extends Fixture
{
     private UserPasswordHasherInterface $passwordHasher;

     public function __construct(UserPasswordHasherInterface $passwordHasher)
     {
         $this->passwordHasher = $passwordHasher;
     }

    public function load(ObjectManager $manager): void
    {
          // Admin user
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@gmail.com');
        $admin->setFullName('System Administrator');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setStatus('active');
        $admin->setCreatedAt(new \DateTimeImmutable());  
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        // Staff user
        $staff = new User();
        $staff->setUsername('staff');
        $staff->setEmail('staff@gmail.com');
        $staff->setFullName('Staff Member1');
        $staff->setRoles(['ROLE_STAFF']);
        $staff->setStatus('active');
        $staff->setCreatedAt(new \DateTimeImmutable());  
        $hashedPassword = $this->passwordHasher->hashPassword($staff, 'staff123');
        $staff->setPassword($hashedPassword);
        $manager->persist($staff);


        $manager->flush();
    }
}