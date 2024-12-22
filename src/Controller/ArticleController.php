<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ArticleController extends AbstractController
{

    #[Route('/article', name: 'app_article')]
    public function index(): Response
    {
        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }

    // findall / for - foreach twig - bloc card bootstrap
    /* 
    #[Route('/article/creer', name: 'app_article_create')]
    public function create(EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $article->setTitre('Mon premier article');
        $article->setTexte('blalbllasjdfsgfyfgsuyfg');
        $article->setPublie(1);
        $article->setDate(new \DateTimeImmutable());
        //dd($article);
        $entityManager->persist($article);
        $entityManager->flush();

        return $this->render('article/creer.html.twig', [
            'controller_name' => 'ArticleController',
            'titre' => 'Article',
            'article' => $article
        ]);
    }

    */

    #[Route('/article/liste', name: 'app_article_liste')]
    public function liste(EntityManagerInterface $entityManager): Response
    {
        $repository = $entityManager->getRepository(Article::class);

        $article = $repository->findAll();

        return $this->render('article/liste.html.twig', [
            'controller_name' => 'ArticleController',
            'titre' => 'Article',
            'article' => $article
        ]);
    }

    /*
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/article/update', name: 'app_article_update')]
    public function update(EntityManagerInterface $entityManager, int $id): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);

        if ($article) {
            throw $this->createNotFoundException(
                'Aucun article trouvé' . $id
            );
        }

        $article->setTitre("Nouveau nom d'article");
        $entityManager->flush();

        return $this->redirectToRoute('app_article_liste');
    }
    */


    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/article/delete/{id}', name: 'app_article_delete')]
    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);

        if ($article) {
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_article_liste');
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/article/new', name: 'app_article_new')]
    public function new(EntityManagerInterface $entityManager,Request $request,SluggerInterface $slugger, #[Autowire('%kernel.project_dir%/public/uploads/image')] string $imageDirectory): Response {
        // just set up a fresh $task object (remove the example data)
        $article = new Article();

        $form = $this->createForm(ArticleType::class, $article);



        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated

            $imageFile = $form->get('image')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $imageFile->move($imageDirectory, $newFilename);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'imageFilename' property to store the PDF file name
                // instead of its contents
                $article->setImage($newFilename);

                $article = $form->getData();
                $this->addFlash('success', 'Article Crée avec succès !');
                $entityManager->persist($article);
                $entityManager->flush();

            }
        }
        return $this->render('article/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/article/edit/{id}', name: 'app_article_edit')]
    public function edit(EntityManagerInterface $entityManager, Request $request, int $id): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'Aucun article trouvé avec l\'ID ' . $id
            );
        }

        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('edit', 'Article modifié avec succès !');
            $entityManager->flush();

            return $this->redirectToRoute('app_article_liste');
        }

        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article,
        ]);
    }



}
