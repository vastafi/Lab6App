<?php

namespace App\Form;

use App\Entity\Image;
use PhpParser\Node\Scalar\String_;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tag', TextType::class)
            ->add('path', FileType::class, [
                'attr' => ['accept' => ".png,.jpg,.jpeg"]
            ]);
        $builder->get('tag')
            ->addViewTransformer(new CallbackTransformer(
                function ($original) {
                    if($original){
                        return implode(',', $original);
                    }
                    else{
                        return '';
                    }
                },
                function ($submitted) {
                    if($submitted){
                        return explode(',', $submitted);
                    }
                    else{
                        return [];
                    }
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Image::class,
        ]);
    }

}
