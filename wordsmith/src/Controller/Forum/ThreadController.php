<?php

namespace App\Controller\Forum;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\Thread;
use App\Form\ThreadType;
use App\Form\SubjectType;
use App\Entity\Subcategory;
use App\Form\EditThreadType;
use App\Entity\HasReadThread;
use App\Repository\PostRepository;
use App\Repository\ThreadRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
/**
 * @Route("/forum")
 */
class ThreadController extends Controller
{
    /**
     * @Route("/", name="thread_index", methods="GET")
     */
    public function index(ThreadRepository $threadRepository): Response
    {
        return $this->render('forum/thread/index.html.twig', ['threads' => $threadRepository->findAll()]);
    }
    /**
     * @Route("/thread/{subcategory_id}/new", name="thread_new", methods="GET|POST")
     */
    public function new(Request $request, UserInterface $user, $subcategory_id): Response
    {   
        $thread = new Thread();
        
        // On récupère la sous-catégorie dans laquelle l'utilisateur poste son sujet
        $repository = $this->getDoctrine()->getRepository(Subcategory::class);
        $subcategory = $repository->findById($subcategory_id);
        $currentSubcategory = $subcategory[0];
        $form = $this->createForm(ThreadType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->request->get('thread');
            $title = $data['title'];
            $subtitle = $data['subtitle'];
            $content = $data['content'];
            $em = $this->getDoctrine()->getManager();
            $thread->setAuthor($user);
            $thread->setTitle($title);
            $thread->setSubcategory($currentSubcategory);
            $thread->setSubtitle($subtitle);
            $em->persist($thread);
            $em->flush();
            $this->createPost($thread, $content, $user);
            $this->createHasRead($thread);
            return $this->redirectToRoute('thread_show', [
                'id' => $thread->getId()
            ]);
        }
        return $this->render('forum/thread/new.html.twig', [
            'thread' => $thread,
            'form' => $form->createView(),
            'subcategory' => $currentSubcategory
        ]);
    }
    public function createHasRead($thread) {
        $repository = $this->getDoctrine()->getRepository(User::class);
        $users = $repository->findAll();
        foreach($users as $user) {
            $hasReadThread = new HasReadThread();
            $em = $this->getDoctrine()->getManager();
            $hasReadThread->setSubcategory($thread->getSubcategory());
            $hasReadThread->setThread($thread);
            $hasReadThread->setUser($user);
            $hasReadThread->setThreadCount(0);
            $hasReadThread->setPostCount(0);
            $hasReadThread->setTimestamp(new \Datetime());
            $em->persist($hasReadThread);
        }
        
        $em->flush(); //Persist objects that did not make up an entire batch
        $em->clear();
    }
    public function createPost($thread, $content, $user)
    {
        $post = new Post();
        $em = $this->getDoctrine()->getManager();
        $post->setThread($thread);
        $post->setContent($content);
        $post->setAuthor($user);
        $em->persist($post);
        $em->flush();
        
    }
    /**
     * @Route("/thread/{id}/page/{page}", name="thread_show", requirements={"page" = "\d+"}, defaults={"page" = 1}, methods="GET|POST")
     */
    public function show(Thread $thread, Request $request, UserInterface $user=null, $page): Response
    {   
        $limit = 10; //limite de questions par page (pagination)
        $postRepository = $this->getDoctrine()->getRepository(Post::class);
        $posts = $postRepository->findByAll($page,$limit, $thread); //requête où on passe la page actuelle, le seeBanned et la limite de questions
        $totalPosts =  $postRepository->findCountMax($thread); //requête qui compte le nombre total de questions avec ou sans les banned
        $pageMax = ceil($totalPosts / $limit); // nombre de page max à afficher (sert pour bouton suivant)
        
        $form = $this->createForm(SubjectType::class, $thread);
        $this->hasRead($thread, $user);
        
        return $this->render('forum/thread/show.html.twig', [
            'thread' => $thread,
            'page'=>$page,
            'pageMax'=>$pageMax,
            'posts'=>$posts,
            'form' => $form->createView() ]);
    }
    public function hasRead($thread, $user)
    {
        $hasReadThread = new HasReadThread();
        $postCount = count($thread->getPosts());
        
        // We need to check if the user visiting the page has already read 
        // this thread
        $repositoryThread = $this->getDoctrine()->getRepository(HasReadThread::class);
        $readThread = $repositoryThread->findTimeStamp($user, $thread);
        if($readThread == false) {
            $em = $this->getDoctrine()->getManager();
            $hasReadThread->setThread($thread);
            $hasReadThread->setThreadCount(count($thread->getSubcategory()->getThreads()));
            $hasReadThread->setUser($user);
            $hasReadThread->setPostCount($postCount);
            $hasReadThread->setTimestamp(new \DateTime());
            $em->persist($hasReadThread);
            $em->flush();
        } else{
            $currentCount = $readThread->getpostCount();
            if($currentCount == null || $currentCount < $postCount) {
                $em = $this->getDoctrine()->getManager();
                $readThread->setPostCount($postCount);
                $readThread->setThreadCount(count($thread->getSubcategory()->getThreads()));
                $em->persist($readThread);
                $em->flush();
            }
        }
    }
    /**
     * @Route("/thread/{id}/move", name="thread_move", methods="POST")
     */
    public function moveThread(Request $request, Thread $thread): Response 
    {
        
        $data = $request->request->get('subject');
        $subcategoryId = $data['subcategory'];
        $repository = $this->getDoctrine()->getRepository(Subcategory::class);
        $subcategory = $repository ->findById($subcategoryId);
        $newSubcategory = $subcategory[0];
        $em = $this->getDoctrine()->getManager();
        $thread->setSubcategory($newSubcategory);
        $em->persist($thread);
        $em->flush();
        return $this->redirectToRoute('forum_subcategory', ['name' => $newSubcategory->getName()]);
    }
    /**
     * @Route("/{id}/edit", name="thread_edit", methods="GET|POST")
     */
    public function edit(Request $request, Thread $thread): Response
    {
        $editForm = $this->createForm(EditThreadType::class, $thread);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('thread_show', ['id' => $thread->getId()]);
        }
        return $this->render('forum/thread/edit.html.twig', [
            'thread' => $thread,
            'editForm' => $editForm->createView(),
        ]);
    }
}