<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Note;
use App\Entity\User;

class NotesController extends ApiController
{
    // private $user;

    // public function __construct()
    // {
    //     $this->user = $this->get('security.token_storage')->getToken()->getUser();
    // }

    /**
     * @Route("/api/notes", name="notes", methods={"POST"})
     */
    public function index()
    {
        // return $this->render('notes/index.html.twig', [
        //     'controller_name' => 'NotesController',
        // ]);
        return $this->json(['id' => 1]);
    }

    /**
     * @Route("/api/note", methods={"PUT"})
     */
    public function setNote(Request $request) 
    {
        $request = $this->transformJsonBody($request);
        $entityManager = $this->getDoctrine()->getManager();
        // $user = $this->get('security.token_storage')->getToken()->getUser();
        $note = new Note();
        $note->setBody($request->get('body'));
        $note->setUser($this->user);
        $entityManager->persist($note);
        $entityManager->flush();

        $this->user->addNote($note);
        
        return $this->json(['id' => $note->getId()]);
    }

    /**
     * @Route("/api/note", methods={"POST"})
     */
    public function getNotesByUser() 
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $notesObjects = $user->getNotes()->toArray();
        foreach ($notesObjects as $key => $note) {
            $notes[$key]['id'] = $note->getId();
            $notes[$key]['body'] = $note->getBody();
            $tags = $note->getTags()->toArray();
            foreach($tags as $keyTag => $tag){
                $notes[$key]['tags'][$keyTag]['id'] =  $tag->getId();
                $notes[$key]['tags'][$keyTag]['body'] = $tag->getBody();
            }
        }
        return $this->json(['notes' => $notes]);
    }
}
