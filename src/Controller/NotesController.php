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
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Note;
use App\Entity\User;
use App\Entity\Tag;
use Datetime;
use Doctrine\ORM\Tools\Pagination\Paginator;

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

    private function paginate($qb, $page = 1, $q = 100, $join = true)
    {
        $firstResult = ($page - 1) * $q;
        $query = $qb->setFirstResult($firstResult)
                    ->setMaxResults($q + $firstResult);
        
        $paginator = new Paginator($query, $fetchJoinCollection = $join);

        return $paginator->getQuery()->getResult();
    }

    private function isEntity($id, $repository, $user) 
    {
        if (!(is_int($id))) {     
            return ['success' => false, 'error' => 'Invalid id'];
        }  

        $ent = $repository->findById($user, $id);

        if ($ent === null) {
            return ['success' => false, 'error' => 'User does not have: ' . $tagId];
        }
        return ['success' => true, 'entity' => $ent];
    }

    /**
     * @Route("/api/set-note", methods={"POST"})
     */
    public function setNote(
        Request $request, 
        UserInterface $user, 
        EntityManagerInterface $entityManager, 
        TagRepository $repository
    ) 
    {
        $request = $this->transformJsonBody($request);

        if (empty($request->get('body'))) {
            return $this->respondError('Invalid body');
        }

        $note = new Note();

        $tagsId = $request->get('tags');
        if (! empty($tagsId)) {

            if (!is_array($tagsId)) {
                return $this->respondError('tags have to be array');
            }

            foreach($tagsId as $id) {
                $result = $this->isEntity($id, $repository, $user);
                if (!$result['success']) {
                    return $this->respondError('Tag error: ' . $result['error']);
                }
                $note->addTag($result['entity']);
            }
        }
        $time = new Datetime();
        $note->setBody($request->get('body'))
            ->setUser($user)
            ->setCreatedAt($time->setTimestamp(time()));

        $entityManager->persist($note);
        $entityManager->flush();
    
        return $this->respondWithSuccess(sprintf('Note %s successfully created', $note->getId()));
    }

    /**
     * @Route("/api/get-notes", methods={"POST"})
     */
    public function getNotes(Request $request, UserInterface $user, NoteRepository $repository) 
    {
        $request = $this->transformJsonBody($request);
        $q = $request->get('q') ?? 10;
        $page = $request->get('page') ?? 1;
        $pagination = $this->paginate($repository->getQB($user), $page, $q, false);

        return new JsonResponse(['notes' => $this->getArrayFromObjects($pagination)]);
    }

    /**
     * @Route("/api/get-count-of-notes", methods={"POST"})
     */
    public function getCountOfNotes(UserInterface $user, NoteRepository $repository) 
    {
        return new JsonResponse(['count of notes' => $repository->getCountByUser($user)]);
    }

    /**
     * @Route("/api/get-notes-by-tag", methods={"POST"})
     */
    public function getNotesByTag(
        Request $request, 
        UserInterface $user, 
        NoteRepository $noteRepository,
        TagRepository $tagRepository
    ) 
    {
        $request = $this->transformJsonBody($request);
        $q = $request->get('q') ?? 10;
        $page = $request->get('page') ?? 1;

        $result = $this->isEntity($request->get('tag'), $tagRepository, $user);

        if (!$result['success']) {     
            return $this->respondError('Tag error: ' . $result['error']);
        }  
        $qb = $noteRepository->getByTagQB($result['entity']->getId());
        $pagination = $this->paginate($qb, $page, $q);
        return new JsonResponse(['notes' => $this->getArrayFromObjects($pagination)]);
    }

    /**
     * @Route("/api/get-note", methods={"POST"})
     */
    public function getNote(Request $request, NoteRepository $repository) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isEntity($request->get('note'), $repository);

        if (!$result['success']) {     
            return $this->respondError('Note error: ' . $result['error']);
        }  

        return new JsonResponse(['note' => $this->getArrayFromObjects([$result['entity']])[0]]);
    }

    /**
     * @Route("/api/del-note", methods={"POST"})
     */
    public function delNote(
        Request $request, 
        EntityManagerInterface $entityManager, 
        NoteRepository $repository
    ) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isEntity($request->get('note'), $repository);

        if (!$result['success']) {     
            return $this->respondError('Note error: ' . $result['error']);
        } 

        $entityManager->remove($result['entity']);
        $entityManager->flush();

        return $this->respondWithSuccess(sprintf('Note successfully deleted'));
    }

    /**
     * @Route("/api/del-notes", methods={"POST"})
     */
    public function delNotes(NoteRepository $repository, UserInterface $user) 
    {
        $del = $repository->delByUser($user);
        
        return $this->respondWithSuccess(sprintf('%s notes successfully deleted', $del));
    }

    /**
     * @Route("/api/del-notes-by-tag", methods={"POST"})
     */
    public function delNotesByTag(
        Request $request, 
        TagRepository $tagRepository,
        NoteRepository $noteRepository, 
        UserInterface $user 
    )
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isEntity($request->get('tag'), $tagRepository, $user);

        if (!$result['success']) {     
            return $this->respondError('Tag error: ' . $result['error']);
        }  

        $count = $noteRepository->getCountByTag($result['entity']->getId());
        $qb = $noteRepository->getByTagQB($result['entity']->getId());
        
        for ($i = 0; $i <= ceil($count / 100); $i++) {
            $pagination = $this->paginate($qb, $i + 1, 100);
            $noteRepository->delByIds($pagination);
        }
        
        return $this->respondWithSuccess(sprintf('notes successfully deleted'));
    }

    /**
     * @Route("/api/change-note-body", methods={"POST"})
     */
    public function changeNoteBody(
        Request $request, 
        EntityManagerInterface $entityManager, 
        NoteRepository $repository,
        UserInterface $user
    ) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isEntity($request->get('note'), $repository, $user);

        if (!$result['success']) {     
            return $this->respondError('Note error: ' . $result['error']);
        }  
        $body = $request->get('body');
        
        if (empty($body)) {
            return $this->respondError('body is empty');
        }
        $result['entity']->setBody($body);
        $entityManager->flush();
        
        return $this->respondWithSuccess(sprintf('Note successfully updated'));
    }
    
    /**
     * @Route("/api/add-tag-to-note", methods={"POST"})
     */
    public function addTagToNote(
        Request $request, 
        EntityManagerInterface $entityManager, 
        NoteRepository $NoteRepository, 
        TagRepository $TagRepository,
        UserInterface $user 
    ) 
    {
        $request = $this->transformJsonBody($request);
        $resultTag = $this->isEntity($request->get('tag'), $TagRepository, $user);

        if (!$resultTag['success']) {
            return $this->respondError('Tag error: ' . $result['error']);
        }

        $resultNote = $this->isEntity($request->get('note'), $NoteRepository, $user);
        
        if (!$resultNote['success']) {
            return $this->respondError('Note error: ' . $result['error']);
        }

        if ($resultNote['entity']->getTags()->contains($resultTag['entity'])) {
            return $this->respondError('This note already has this tag');
        }

        $resultNote['entity']->addTag($resultTag['entity']);
        $entityManager->flush();

        return $this->respondWithSuccess(sprintf('Note %s successfully updated', $resultNote['entity']->getId()));
    }

    /**
     * @Route("/api/del-tag-from-note", methods={"POST"})
     */
    public function delTagFromNote(
        Request $request, 
        EntityManagerInterface $entityManager, 
        NoteRepository $NoteRepository, 
        TagRepository $TagRepository,
        UserInterface $user 
    ) 
    {
        $request = $this->transformJsonBody($request);
        $resultTag = $this->isEntity($request->get('tag'), $TagRepository, $user);

        if (!$resultTag['success']) {
            return $this->respondError('Tag error: ' . $result['error']);
        }

        $resultNote = $this->isEntity($request->get('note'), $NoteRepository, $user);
        
        if (!$resultNote['success']) {
            return $this->respondError('Note error: ' . $result['error']);
        }

        if (!$resultNote['entity']->getTags()->contains($resultTag['entity'])) {
            return $this->respondError('This note has not this tag');
        }
        $resultNote['entity']->removeTag($resultTag['entity']);
        $entityManager->flush();

        return $this->respondWithSuccess(sprintf('Note %s successfully updated', $resultNote['entity']->getId()));
    }
}
