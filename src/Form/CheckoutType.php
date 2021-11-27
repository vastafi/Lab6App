<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('paymentDetails', ChoiceType::class,[
                'choices' => [
                    'Cash' => 'Cash',
                    'Credit Card' => 'Credit Card'
                ],
                'label' => 'Payment method'
            ])


            ->add('creditCardDetails',CreditCardDetailsType::class,[
                'mapped'=> true,
                'required' => false
            ])
            ->add('shippingDetails', ShippingDetailsType::class)
            ->add('submit', SubmitType::class, [
                'label' => 'Place Order',
                'attr' => ['class' => 'btn-success']
            ]);

        $builder->get('paymentDetails')
            ->addModelTransformer(new CallbackTransformer(
                function ($paymentArray) {
                    return count($paymentArray) ? $paymentArray[0] :null;
                },
                function ($paymentArray) {
                    return [$paymentArray];
                }
            ));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
