<?php


namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\CardScheme;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class CreditCardDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('creditCardCode', NumberType::class, [
                'label' => 'Code',
                'scale' => 0,
                'constraints' => [
                    new CardScheme(['VISA','MASTERCARD','MAESTRO','AMEX'])
                ]

            ])
            ->add('cvv', NumberType::class, [
                'label' => 'CVV',
                'constraints' => [
                    new Length(3)
                ],
            ])
            ->add('expiresAt', TextType::class, [
                'constraints' => [
                    new Regex('/^(0[1-9]|1[0-2])\/?([0-9]{4}|[0-9]{2})$/', 'Enter a valid value'),
                ],
            ]);
        $builder->get('creditCardCode')->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $data =(str_replace(' ', '', $data));

            $event->setData($data);
        });
    }

}