<?php


namespace App\Form;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ShippingDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('country',CountryType::class,[
                'attr' => ['style' => 'width: 80%;
                            height: 35px;
                            border: 1px solid #dadada;
                            color: #666666;']
            ])
            ->add('state',TextType::class,[
                'attr' => ['pattern' => '[a-zA-Z" "]*'
                ]
            ])
            ->add('city',TextType::class,[
                'attr' => ['pattern' => '[a-zA-Z" "-\']*'
                ]
            ])
            ->add('address1',TextType::class,[
                'required' => true
            ])
            ->add('address2',TextType::class,[
                'required' => false,
                'help' => 'This field is optional'
            ])
        ;
    }

}