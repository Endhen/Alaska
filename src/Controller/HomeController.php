<?php

namespace MicroCMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use MicroCMS\Domain\User;
use MicroCMS\Domain\Comment;
use MicroCMS\Form\Type\CommentType;
use MicroCMS\Form\Type\ReponseType;
use MicroCMS\Form\Type\VisitorType;


class HomeController {

    /**
     * Home page controller.
     *
     * @param Application $app Silex application
     */
    public function indexAction(Application $app) {
        $articles = $app['dao.article']->findAllPublied(true);
        return $app['twig']->render('index.html.twig', array('articles' => $articles));
    }
    
    /**
     * Article details controller.
     *
     * @param integer $id Article id
     * @param Request $request Incoming request
     * @param Application $app Silex application
     */
    public function articleAction($id, Request $request, Application $app) {
        
        $article = $app['dao.article']->find($id);
        $commentFormView = NULL;
        $reponseFormView = NULL;
        $user = $app['user'];
        
        if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
            // A user is fully authenticated : he can ...
            
            // ... add reponse
            $reponse = new Comment($article, $user);
            
            // ... add comments
            $comment = new Comment($article, $user);
            
            // Fills comment content
            $commentForm = $app['form.factory']->create(CommentType::class, $comment);
            $commentForm->handleRequest($request);
            
            // Fills response childOf, content
            $reponseForm = $app['form.factory']->create(ReponseType::class, $reponse);
            $reponseForm->handleRequest($request);
            
            
            
            if ($reponseForm->isSubmitted() && $reponseForm->isValid()) {
                
                // Save to set an id with sql auto increment
                $app['dao.comment']->save($reponse);
                $reponse = $app['dao.comment']->findLatest();
                
                // Parent Object
                $parent = $app['dao.comment']->find($reponse->getChildOf());
                
                //comment object array
                $childArray = $parent->getParentOf('array');
                array_push($childArray, $reponse);
                $parent->setParentOf($childArray);
                
                $app['dao.comment']->save($parent);
                $app['session']->getFlashBag()->add('success', 'Votre réponse a bien été ajouté.');
                
            }
            
            if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                $app['dao.comment']->save($comment);
                $app['session']->getFlashBag()->add('success', 'Votre commentaire a bien été ajouté.');
            }
            
            $reponseFormView = $reponseForm->createView();
            $commentFormView = $commentForm->createView();
        }
        
        $comments = $app['dao.comment']->findAllByArticle($id);
        
        return $app['twig']->render('article.html.twig', array(
            'user' => $user,
            'article' => $article,
            'comments' => $comments,
            'commentForm' => $commentFormView,
            'reponseForm' => $reponseFormView));
    }
    
    /**
     * Report controller.
     *
     * @param integer $id comment id
     * @param Application $app Silex application
     */
    public function reportAction($id, Application $app) {
        
        if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
            
            $userId = $app['user']->getId();
            $comment = $app['dao.comment']->find($id)->setReports('add', $userId);
            
            $app['dao.comment']->save($comment);
            
            // Redirect 
            $article = $comment->getArticle();
            return $app->redirect($app['url_generator']->generate('article', array('id' => $article->getId())));
        }
    }
    
    /**
     * Cancel report controller.
     *
     * @param integer $id comment id
     * @param Application $app Silex application
     */
    public function cancelReportAction($id, Application $app) {
        if ($app['security.authorization_checker']->isGranted('IS_AUTHENTICATED_FULLY')) {
            
            $userId = $app['user']->getId();
            $comment = $app['dao.comment']->find($id)->setReports('suppr', $userId);
            
            $app['dao.comment']->save($comment);
            
            // Redirect 
            $article = $comment->getArticle();
            return $app->redirect($app['url_generator']->generate('article', array('id' => $article->getId())));
        }
    }
    
    /**
     * User login controller.
     *
     * @param Request $request Incoming request
     * @param Application $app Silex application
     */
    public function loginAction(Request $request, Application $app) {
        return $app['twig']->render('login.html.twig', array(
            'error'         => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
        ));
    }
    
    public function registingVisitorAction(Request $request, Application $app) {
        $user = new User();
        $userForm = $app['form.factory']->create(VisitorType::class, $user);
        $userForm->handleRequest($request);
        
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            $user->initPassword($app);
            
            if($app['dao.user']->save($user)) {
                $app['session']->getFlashBag()->add('success', 'L\'utilisateur a bien été créé. Vous pouvez maintenant vous connecter');
            } else {
                $app['session']->getFlashBag()->add('warning', 'Ce nom d\'utilisateur existe déjà, veuillez trouver un autre nom d\'utilisateur.');
            };
            
        }
        return $app['twig']->render('visitor_form.html.twig', array(
            'title' => 'Inscription',
            'userForm' => $userForm->createView()));
    }
}
