<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Note;
use App\Entity\User;

class NotesController extends AbstractController
{
    /**
     * @Route("/notes", name="notes")
     */
    public function index()
    {
        return $this->render('notes/index.html.twig', [
            'controller_name' => 'NotesController',
        ]);
    }

    /**
     * @Route("/setnote", methods={"POST"})
     */
    public function setNote(Request $request) 
    {
        $entityManager = $this->getDoctrine()->getManager();
        
        $user = new User();
        $user->setEmail('olga-q@ro.ru');
        $user->setRoles([]);
        $user->setPassword('924655lol');
        $entityManager->persist($user);

        $entityManager->flush();

        $note = new Note();
        $note->setBody($request->query->get('body'));
        $note->setUser($user);
        $entityManager->persist($note);

        $entityManager->flush();

        return $this->json(['id' => $note->getId()]);
    }
}
