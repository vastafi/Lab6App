<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('items', TextareaType::class)
            ->add('paymentDetails', ChoiceType::class, [
                'choices' => [
                    'Cash' => 'Cash',
                    'Credit Card' => 'Credit Card'
                ],
                'label' => 'Payment method'
            ])
            ->add('shippingDetails', ShippingDetailsType::class)
            ->add('total', NumberType::class);

//        $builder->get('items')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
//            $items = $event->getData();
//            $form = $event->getForm();
//            $itemsArr = json_decode($items, true);
//
//            $total = 0;
//            if (is_array($itemsArr)) {
//                foreach ($itemsArr as $item) {
//                    $total += $item['amount'] * $item['price'];
//
//                }
//            }
//
////            $form->getParent()->add('total', NumberType::class);
//            $form->getParent()->get('total')->setData($total);
//
//        });

        $builder->get('paymentDetails')
            ->addModelTransformer(new CallbackTransformer(
                function ($paymentArray) {
                    return count($paymentArray) ? $paymentArray[0] : null;
                },
                function ($paymentArray) {
                    return [$paymentArray];
                }
            ));

        $builder->get('items')
            ->addModelTransformer(new CallbackTransformer(
                function ($itemsArray) {
                    return json_encode($itemsArray);
                },
                function ($itemsJson) {
                    return json_decode($itemsJson);
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
