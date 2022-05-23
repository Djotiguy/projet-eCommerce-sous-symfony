<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Category;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function registerUser(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $passHasher): Response
    {
        //Cette méthode présente à l'Utilisateur un formulaire de création d'Utilisateur, afin de créer une nouvelle Entity User

        //Pour enregistrer un nouveal utilisateur, nous avons besoin de l'Entity Manager
        $entityManager = $doctrine->getManager();
        //Nous récupérons la liste des Categories pour notre navbar
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();
        //Nous créons notre formulaire interne
        $userForm = $this->createFormBuilder()
            ->add('username', TextType::class, [
                'label' => 'Nom de l\'Utilisateur',
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'class' => 'w3-input w3-border w3-round w3-light-grey',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmation du mot de passe',
                    'attr' => [
                        'class' => 'w3-input w3-border w3-round w3-light-grey',
                    ],
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Privilèges',
                'choices' => [
                    'Role: Client' => 'ROLE_CLIENT', //NOM DU CHAMP => VALEUR DU CHAMP
                    'Role: Admin' => 'ROLE_ADMIN',
                ],
                'expanded' => true, //Boutons plutôt que menu déroulant
                'multiple' => false, //On est soit Client, soit Admin   
                'attr' => [
                    'class' => 'w3-input w3-border w3-round w3-light-grey',
                ],          
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Inscription',
                'attr' => [
                    'class' => 'w3-button w3-black w3-margin-bottom',
                    'style' => 'margin-top: 10px',
                ]
            ])
            ->getForm()
        ;
        //Nous traitons les données reçues au sein de notre formulaire
        $userForm->handleRequest($request);
        //Si le formulaire est rempli et valide, nous le prenons en charge
        if($userForm->isSubmitted() && $userForm->isValid()){
            //On récupère les informations de notre formulaire
            $data = $userForm->getData();
            //Nous créons et renseignons notre Entity User
            $user = new User;
            $user->setUsername($data['username']);
            $user->setRoles(['ROLE_USER', $data['roles']]);
            $user->setPassword($passHasher->hashPassword($user, $data['password']));
            $entityManager->persist($user);
            $entityManager->flush();
            //Après le transfert de l'instance User vers la BDD, nous retournons à l'index
            return $this->redirectToRoute('app_index');
        }
        //Si notre formulaire n'est pas validé, nous le présentons à l'Utilisateur
        return $this->render('index/dataform.html.twig', [
            'categories' => $categories,
            'formName' => 'Inscription Utilisateur',
            'dataForm' => $userForm->createView(),
        ]);
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
