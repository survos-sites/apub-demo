<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Event\PostPublishedEvent;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Survos\ActivityPubBundle\Repository\ActivityPubActorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class PostController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PostRepository $posts,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ActivityPubActorRepository $actors,
    ) {
    }

    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        return $this->render('post/index.html.twig', [
            'posts' => $this->posts->findAllNewestFirst(),
        ]);
    }

    #[Route('/posts/{id}', name: 'app_post_show')]
    public function show(Post $post, Request $request): Response
    {
        // Lazily created on first publish (see FederatePostListener) — null until then.
        $actor = $this->actors->findOneBySubject('user', (string) $post->author->getId());

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'authorHandle' => $actor ? sprintf('%s@%s', $actor->username, $request->getHost()) : null,
        ]);
    }

    #[Route('/posts/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $data = ['title' => '', 'body' => ''];
        $form = $this->createForm(PostType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $submitted = $form->getData();

            $post = new Post(
                author: $user,
                title: $submitted['title'],
                body: $submitted['body'],
            );
            $this->em->persist($post);
            $this->em->flush();

            $this->eventDispatcher->dispatch(new PostPublishedEvent($post));

            $this->addFlash('success', 'Post published.');

            return $this->redirectToRoute('app_post_show', ['id' => $post->id]);
        }

        return $this->render('post/new.html.twig', ['form' => $form]);
    }
}
