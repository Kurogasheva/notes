<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\NoteRepository;
use App\Repository\TagRepository;
use App\Entity\Note;
use App\Entity\User;
use App\Entity\Tag;
use Datetime;

class NotesController extends ApiController
{
    private function getArrayFromObjects($objects) 
    {
        $notes = [];
        foreach ($objects as $key => $note) {
            $notes[$key]['id'] = $note->getId();
            $notes[$key]['body'] = $note->getBody();
            $notes[$key]['created_at'] = $note->getCreatedAt()->format('Y-m-d\ H:i:s');
            foreach($note->getTags() as $keyTag => $tag){
                $notes[$key]['tags'][$keyTag]['id'] =  $tag->getId();
                $notes[$key]['tags'][$keyTag]['body'] = $tag->getBody();
            }
        }
        return $notes;
    }

    protected function isTag($tagId) 
    {
        if (!(is_int($tagId))) {     
            return ['success' => false, 'error' => 'Invalid tag id'];
        }  

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $tag = $this->getDoctrine()->getRepository(Tag::class)->findById($user, $tagId);

        if ($tag === null) {
            return ['success' => false, 'error' => 'User does not have this tag: ' . $tagId];
        }
        return ['success' => true, 'tag' => $tag];
    }

    protected function isNote($noteId) 
    {
        if (!(is_int($noteId))) {     
            return ['success' => false, 'error' => 'Invalid note id'];
        }  

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $note = $this->getDoctrine()->getRepository(Note::class)->findById($user, $noteId);

        if ($note === null) {
            return ['success' => false, 'error' => 'User does not have this note: ' . $noteId];
        }
        return ['success' => true, 'note' => $note];
    }

    /**
     * @Route("/api/set-note", methods={"POST"})
     */
    public function setNote(Request $request) 
    {
        $request = $this->transformJsonBody($request);

        if (empty($request->get('body'))) {
            return $this->respondError('Invalid body');
        }
        
        $entityManager = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $note = new Note();

        $tagsId = $request->get('tags');
        if (! empty($tagsId)) {
            if (!is_array($tagsId)) {
                return $this->respondError('tags have to be array');
            }
            foreach($tagsId as $id) {
                $result = $this->isTag($id);
                if (!$result['success']) {
                    return $this->respondError($result['error']);
                }
                $note->addTag($result['tag']);
            }
        }
        $time = new Datetime();
        $note->setBody($request->get('body'))
            ->setUser($user)
            ->setCreatedAt($time->setTimestamp(time()))
        ;

        $entityManager->persist($note);
        $entityManager->flush();
    
        return $this->respondWithSuccess(sprintf('Note %s successfully created', $note->getId()));
    }

    /**
     * @Route("/api/get-notes", methods={"POST"})
     */
    public function getNotes() 
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $notesObjects = $this->getDoctrine()->getRepository(Note::class)->findByUser($user);

        return new JsonResponse(['notes' => $this->getArrayFromObjects($notesObjects)]);
    }

    /**
     * @Route("/api/get-notes-by-tag", methods={"POST"})
     */
    public function getNotesByTag(Request $request) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isTag($request->get('tag'));

        if (!$result['success']) {     
            return $this->respondError($result['error']);
        }  

        $notesObjects = $result['tag']->getNotes();

        return new JsonResponse(['notes' => $this->getArrayFromObjects($notesObjects)]);
    }

    /**
     * @Route("/api/get-note", methods={"POST"})
     */
    public function getNote(Request $request) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isNote($request->get('note'));

        if (!$result['success']) {     
            return $this->respondError($result['error']);
        }  

        return new JsonResponse(['note' => $this->getArrayFromObjects([$result['note']])[0]]);
    }

    /**
     * @Route("/api/del-note", methods={"POST"})
     */
    public function delNote(Request $request) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isNote($request->get('note'));

        if (!$result['success']) {     
            return $this->respondError($result['error']);
        } 

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($result['note']);
        $entityManager->flush();

        return $this->respondWithSuccess(sprintf('Note successfully deleted'));
    }

    /**
     * @Route("/api/del-notes-by-user", methods={"POST"})
     */
    public function delNotes() 
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $del = $this->getDoctrine()->getRepository(Note::class)->delByUser($user);
        
        return $this->respondWithSuccess(sprintf('%s notes successfully deleted', $del));
    }

    /**
     * @Route("/api/del-notes-by-tag", methods={"POST"})
     */
    public function delNotesByTag(Request $request) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isTag($request->get('tag'));

        if (!$result['success']) {     
            return $this->respondError($result['error']);
        }  

        $notes = $result['tag']->getNotes();
        $entityManager = $this->getDoctrine()->getManager();
        
        foreach ($notes as $key => $note) {
            $entityManager->remove($note);
        }

        $entityManager->flush();
        
        return $this->respondWithSuccess(sprintf('Notes successfully deleted'));
    }

    /**
     * @Route("/api/change-note-body", methods={"POST"})
     */
    public function changeNoteBody(Request $request) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isNote($request->get('note'));

        if (!$result['success']) {     
            return $this->respondError($result['error']);
        }  
        $body = $request->get('body');
        if (empty($body)) {
            return $this->respondError('body is empty');
        }
        $entityManager = $this->getDoctrine()->getManager();
        $result['note']->setBody($body);
        $entityManager->flush();
        
        return $this->respondWithSuccess(sprintf('Note successfully updated'));
    }
    
    /**
     * @Route("/api/add-tag-to-note", methods={"POST"})
     */
    public function addTagToNote(Request $request) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isTag($request->get('tag'));

        if (!$result['success']) {
            return $this->respondError($result['error']);
        }

        $result = array_merge($result, $this->isNote($request->get('note')));
        
        if (!$result['success']) {
            return $this->respondError($result['error']);
        }

        if ($result['note']->getTags()->contains($result["tag"])) {
            return $this->respondError('This note already has this tag');
        }

        $entityManager = $this->getDoctrine()->getManager();
        $result['note']->addTag($result['tag']);
        $entityManager->flush();

        return $this->respondWithSuccess(sprintf('Note %s successfully updated', $result['note']->getId()));
    }

    /**
     * @Route("/api/del-tag-from-note", methods={"POST"})
     */
    public function delTagFromNote(Request $request) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isTag($request->get('tag'));

        if (!$result['success']) {
            return $this->respondError($result['error']);
        }

        $result = array_merge($result, $this->isNote($request->get('note')));

        if (!$result['success']) {
            return $this->respondError($result['error']);
        }

        if (!$result['note']->getTags()->contains($result["tag"])) {
            return $this->respondError('This note has not this tag');
        }
        
        $entityManager = $this->getDoctrine()->getManager();
        $result['note']->removeTag($result['tag']);
        $entityManager->flush();

        return $this->respondWithSuccess(sprintf('Note %s successfully updated', $result['note']->getId()));
    }
}
