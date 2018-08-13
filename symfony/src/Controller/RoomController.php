<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Room;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/room")
 */
class RoomController extends Controller
{
    /**
     * @Route("/", name="room_index", methods="GET")
     */
    public function index(RoomRepository $roomRepository): Response
    {
        return $this->render('room/index.html.twig', [
            'rooms' => $roomRepository->findAll()]);
    }

    /**
     * @Route("/new", name="room_new", methods="GET|POST")
     */
    public function new(Request $request, UserInterface $user): Response
    {   
        $room = new Room();
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        $code = $this->createRandomCode();
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $room->setDungeonmaster($user);
            $room->setCode($code);
            $em->persist($room);
            $em->flush();

            return $this->redirectToRoute('room_link', ['code' => $code ]);
        }

        return $this->render('room/new.html.twig', [
            'room' => $room,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{code}", name="room_link", methods="GET")
     */
    public function getRoomLink(Room $room) {
        dump($room);die;

    }
    

    private function createRandomCode() { 

        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ023456789"; 
        srand((double)microtime()*1000000); 
        $i = 0; 
        $pass = '' ; 
    
        while ($i <= 10) { 
            $num = rand() % 33; 
            $tmp = substr($chars, $num, 1); 
            $pass = $pass . $tmp; 
            $i++; 
        } 
    
        return $pass; 
    } 

    /**
     * @Route("/{id}", name="room_show", methods="GET")
     */
    public function show(Room $room): Response
    {
        return $this->render('room/show.html.twig', ['room' => $room]);
    }

    /**
     * @Route("/{id}/edit", name="room_edit", methods="GET|POST")
     */
    public function edit(Request $request, Room $room): Response
    {
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('room_edit', ['id' => $room->getId()]);
        }

        return $this->render('room/edit.html.twig', [
            'room' => $room,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="room_delete", methods="DELETE")
     */
    public function delete(Request $request, Room $room): Response
    {
        if ($this->isCsrfTokenValid('delete'.$room->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($room);
            $em->flush();
        }

        return $this->redirectToRoute('room_index');
    }
}
