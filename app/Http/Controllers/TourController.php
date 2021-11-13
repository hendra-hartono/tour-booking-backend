<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\TourDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TourController extends Controller
{
    public function index()
    {
        $limit = 10;
        if (request('limit')) {
            $limit = request('limit');
        }

        $tours = Tour::when(request('status'), function ($query) {
            return $query->where('status', request('status'));
        })->paginate($limit);

        return response()->json($tours);
    }

    public function show($id)
    {
        $tour = Tour::with('tour_date')->find($id);

        return response()->json($tour);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:255',
            'itinerary' => 'required',
            'status' => 'required|in:Draft,Public',
            'tour_date' => 'required|array',
            'tour_date.*.date' => 'required|date',
            'tour_date.*.status' => 'required|in:Enabled,Disabled'
        ]);

        DB::beginTransaction();

        try {
            $tour = new Tour();
            $tour->name = $request->name;
            $tour->itinerary = $request->itinerary;
            $tour->status = $request->status;
            $tour->save();

            $data_tour_date = array();

            foreach ($request->tour_date as $item) {
                $data_tour_date[] = array(
                    'tour_id' => $tour->id,
                    'date' => $item['date'],
                    'status' => $item['status']
                );
            }

            TourDate::insert($data_tour_date);

            DB::commit();
            return response()->json($tour, 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
            //return response('Failed to create tour', 400);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|max:255',
            'itinerary' => 'required',
            'status' => 'required|in:Draft,Public',
            'tour_date' => 'required|array',
            'tour_date.*.date' => 'required|date',
            'tour_date.*.status' => 'required|in:Enabled,Disabled'
        ]);

        $tour = Tour::findOrFail($id);
        $tour->name = $request->name;
        $tour->itinerary = $request->itinerary;
        $tour->status = $request->status;
        $tour->save();


        $data_tour_date = array();
        $new_tour_date_ids = array();

        foreach ($request->tour_date as $item) {
            $data_tour_date[] = array(
                'id' => $item['id'],
                'tour_id' => $tour->id,
                'date' => $item['date'],
                'status' => $item['status']
            );

            if ($item['id']) $new_tour_date_ids[] = $item['id'];
        }

        // -- TOUR DATE : delete old data that not included in the new payload
        TourDate::whereNotIn('id', $new_tour_date_ids)
            ->where('tour_id', $tour->id)
            ->delete();

        // -- TOUR DATE : update existing data or create new ones
        TourDate::upsert(
            $data_tour_date,
            ['id', 'tour_id'],
            ['date', 'status']
        );

        return response()->json($tour, 200);
    }

    public function destroy($id)
    {
        // Tour::findOrFail($id)->delete();
        TourDate::where('tour_id', $id)->delete();
        Tour::destroy($id);

        return response()->json(['message' => 'Deleted Successfully'], 200);
    }
}
