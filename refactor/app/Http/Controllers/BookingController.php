<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        
        $user = $request->get('__authenticatedUser');
        $response = $user->user_type === env('ADMIN_ROLE_ID') || $user->user_type === env('SUPERADMIN_ROLE_ID')
            ? $this->repository->getAll($request): $this->repository->getUsersJobs($user->id);

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $response = $this->repository->store($request->__authenticatedUser,$request->all());

        return response($response);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $response = $this->repository->updateJob($id, $request->except(['_token', 'submit']), $request->__authenticatedUser);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $response = $this->repository->storeJobEmail($request->all());
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        $user_id = $request->get('user_id');
        if($user_id) {
            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $response = $this->repository->acceptJob($request->all(), $request->__authenticatedUser);
        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {

        $response = $this->repository->acceptJobWithId($request->get('job_id'), $request->__authenticatedUser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {

        $response = $this->repository->cancelJobAjax($request->all(), $request->__authenticatedUser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {

        $response = $this->repository->endJob($request->all());

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
    
        $response = $this->repository->customerNotCall($request->all());

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {

        $response = $this->repository->getPotentialJobs($request->__authenticatedUser);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        $jobId = $data['jobid'] ?? null;
        if ($jobId) {
            Distance::where('job_id', $jobId)->update([
                'distance' => $data['distance'] ?? null,
                'time' => $data['time'] ?? null,
            ]);

            Job::where('id', $jobId)->update([
                'admin_comments' => $data['admincomment'] ?? null,
                'flagged' => $data['flagged'] === 'true' ? 'yes' : 'no',
                'session_time' => $data['session_time'] ?? null,
                'manually_handled' => $data['manually_handled'] === 'true' ? 'yes' : 'no',
                'by_admin' => $data['by_admin'] === 'true' ? 'yes' : 'no',

            ]);

        }

        return response('Record updated!');
    }

    public function reopen(Request $request)
    {
    
        $response = $this->repository->reopen($request->all());

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}