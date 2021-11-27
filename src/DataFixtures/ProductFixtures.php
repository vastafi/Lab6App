<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        foreach (range(0, 1000) as $index){
            $product = new Product();
            $product->setCategory($faker->randomElement(['Cars', 'Toys', 'Misc', 'Music', 'Tools', 'PC parts']));
            $product->setDescription($faker->text(50));
            $product->setName($faker->words(2, true));
            $product->setCode('AB'.$faker->unique()->numerify('#####'));
            $product->setPrice($faker->randomFloat(0, 100, 2400));
            $product->setAvailableAmount($faker->randomNumber(3));
            $product->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($product);
        }

        $manager->flush();
    }
}
