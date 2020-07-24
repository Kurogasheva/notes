<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Note;
use App\Entity\User;

class UsersController extends AbstractController
{
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


    public function getAuthUser() 
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        return $this->json(["user id" => $user->getId()]);
    }
}
