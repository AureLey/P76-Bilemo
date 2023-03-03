<?php

declare(strict_types=1);

/*
 * This file is part of Bilemo
 *
 * (c)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\Consumer;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Creation of Products
        for ($i = 0; $i < 20; ++$i) {
            $product = new Product();
            $product->setName('nameProduct'.$i);
            $product->setDescription('Lorem ipsum dolor sit amet, consectetur adipisicing elit. Consequatur, aspernatur.');
            $product->setPrice(rand(100, 150) * $i);

            $manager->persist($product);
        }
        // -------------------------------------------------

        // Creation of Users
        for ($i = 0; $i < 4; ++$i) {
            $user = new User();
            $user->setemail('user'.$i.'@gmail.com');
            $user->setPassword($this->userPasswordHasher->hashPassword($user, 'user'.$i));
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);

            // Creations of Consumers
            $nbConsumers = rand(4, 9);
            for ($j = 0; $j < $nbConsumers; ++$j) {
                $consumer = new Consumer();
                $consumer->setFirstname('userFirstname'.$j);
                $consumer->setLastname('userLastname'.$j);
                $consumer->setUser($user);
                $manager->persist($consumer);
            }
        }

        $manager->flush();
    }
}
