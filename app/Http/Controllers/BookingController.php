<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Passenger;
use App\Models\Invoice;
use App\Models\Tour;
use App\Models\TourDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index_passenger()
    {
        $limit = 10;
        if (request('limit')) {
            $limit = request('limit');
        }

        $passengers = Passenger::when(request('status'), function ($query) {
            return $query->where('status', request('status'));
        })->simplePaginate($limit);

        return response()->json($passengers);
    }

    public function index()
    {
        $limit = 10;
        if (request('limit')) {
            $limit = request('limit');
        }

        $bookings = Booking::when(request('status'), function ($query) {
            return $query->where('status', request('status'));
        })->simplePaginate($limit);

        return response()->json($bookings);
    }

    public function show($id)
    {
        $booking = Booking::with([
            'tour',
            'booking_passenger',
            'booking_passenger.passenger',
            'invoice'
        ])->find($id);

        return response()->json($booking);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'tour_id' => 'required|numeric',
            'tour_date' => 'required|date',
            'booking_passenger' => 'required|array',
            'booking_passenger.*.given_name' => 'required|max:128',
            'booking_passenger.*.surname' => 'required|max:64',
            'booking_passenger.*.email' => 'required|email|max:128',
            'booking_passenger.*.mobile' => 'required|max:16',
            'booking_passenger.*.passport' => 'required|max:16',
            'booking_passenger.*.birth_date' => 'required|date',
        ]);

        // Business Logic validation
        $tour = Tour::where(['id' => $request->tour_id, 'status' => 'Public'])->first();
        if (!$tour) {
            return response('Tour is not found or not set as Public', 404);
        }

        // Business Logic validation
        $tour_date = TourDate::where(['tour_id' => $request->tour_id, 'date' => $request->tour_date, 'status' => 'Enabled'])->first();
        if (!$tour_date) {
            return response('Tour Date is not available', 404);
        }

        DB::beginTransaction();

        try {
            $booking = new Booking();
            $booking->tour_id = $request->tour_id;
            $booking->tour_date = $request->tour_date;
            $booking->status = "Submitted"; // default value
            $booking->save();

            $data_booking_passenger = array();

            foreach ($request->booking_passenger as $item) {
                $passenger = Passenger::updateOrCreate(
                    ['id' => $item['passenger_id']],
                    [
                        'given_name' => $item['given_name'],
                        'surname' => $item['surname'],
                        'email' => $item['email'],
                        'mobile' => $item['mobile'],
                        'passport' => $item['passport'],
                        'birth_date' => $item['birth_date']
                    ]
                );

                $data_booking_passenger[] = array(
                    'booking_id' => $booking->id,
                    'passenger_id' => $passenger->id,
                    'special_request' => $item['special_request'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                );
            }

            BookingPassenger::insert($data_booking_passenger);

            DB::commit();
            return response()->json($booking, 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
            //return response('Failed to create booking', 400);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'tour_id' => 'required|numeric',
            'tour_date' => 'required|date',
            'status' => 'required|in:Submitted,Confirmed,Cancelled',
            'booking_passenger' => 'required|array',
            'booking_passenger.*.given_name' => 'required|max:128',
            'booking_passenger.*.surname' => 'required|max:64',
            'booking_passenger.*.email' => 'required|email|max:128',
            'booking_passenger.*.mobile' => 'required|max:16',
            'booking_passenger.*.passport' => 'required|max:16',
            'booking_passenger.*.birth_date' => 'required|date',
        ]);

        // Business Logic validation
        $tour = Tour::where(['id' => $request->tour_id, 'status' => 'Public'])->first();
        if (!$tour) {
            return response('Tour is not found or not set as Public', 404);
        }

        // Business Logic validation
        $tour_date = TourDate::where(['tour_id' => $request->tour_id, 'date' => $request->tour_date, 'status' => 'Enabled'])->first();
        if (!$tour_date) {
            return response('Tour Date is not available', 404);
        }

        $booking = Booking::findOrFail($id);
        $booking->tour_id = $request->tour_id;
        $booking->tour_date = $request->tour_date;
        $booking->status = $request->status;
        $booking->save();


        $data_booking_passenger = array();
        $new_booking_passenger_ids = array();

        foreach ($request->booking_passenger as $item) {
            $passenger = Passenger::updateOrCreate(
                ['id' => $item['passenger_id']],
                [
                    'given_name' => $item['given_name'],
                    'surname' => $item['surname'],
                    'email' => $item['email'],
                    'mobile' => $item['mobile'],
                    'passport' => $item['passport'],
                    'birth_date' => $item['birth_date']
                ]
            );

            $tmp = array(
                'id' => $item['id'],
                'booking_id' => $booking->id,
                'passenger_id' => $passenger->id,
                'special_request' => $item['special_request'],
                'updated_at' => date('Y-m-d H:i:s')
            );
            if (!$item['id']) $tmp['created_at'] = date('Y-m-d H:i:s');
            $data_booking_passenger[] = $tmp;

            if ($item['id']) $new_booking_passenger_ids[] = $item['id'];
        }

        // -- BOOKING PASSENGER : delete old data that not included in the new payload
        // -- PASSENGER : passenger data is not deleted on purpose for data collection
        BookingPassenger::whereNotIn('id', $new_booking_passenger_ids)
            ->where('booking_id', $booking->id)
            ->delete();

        // -- BOOKING PASSENGER : update existing data or create new ones
        BookingPassenger::upsert(
            $data_booking_passenger,
            ['id', 'booking_id'],
            ['passenger_id', 'special_request']
        );

        return response()->json($booking, 200);
    }
}
