<?php

namespace MicroCMS\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints as Assert;

class VisitorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, array(
                'invalid_message' => 'Le champ "Nom d\'utilisateur" doit être valide',
                'required' => true,
                'label' => 'Nom d\'utilisateur',
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('min' => 5,
                                           'max' => 20)))))
            ->add('password', RepeatedType::class, array(
                'type'            => PasswordType::class, 
                'invalid_message' => 'Les champs "mot de passe" doivent êtres identiques', 
                'options'         => array('required' => true), 
                'first_options'   => array('label' => 'Mot de passe'), 
                'second_options'  => array('label' => 'Répétez le mot de passe'), 
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('min' => 5,
                                           'max' => 20)))));
    }

    public function getName()
    {
        return 'user';
    }
}
