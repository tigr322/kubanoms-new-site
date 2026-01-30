<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class VirtualReceptionController extends Controller
{
    /**
     * Показать форму виртуальной приемной с проверкой ЕСИА
     */
    public function index()
    {
        // Проверяем middleware уже сработал
        $esiaUser = Session::get('esia_user');

        return Inertia::render('VirtualReception/Create', [
            'esiaUser' => $esiaUser,
            'preFilledData' => [
                'lastName' => $esiaUser['lastName'] ?? '',
                'firstName' => $esiaUser['firstName'] ?? '',
                'middleName' => $esiaUser['middleName'] ?? '',
                'email' => $esiaUser['email'] ?? '',
                'phone' => $esiaUser['phone'] ?? '',
                'snils' => $esiaUser['snils'] ?? '',
            ]
        ]);
    }

    /**
     * Callback от esia-mini после авторизации
     */
    public function callback(Request $request)
    {
        // Получаем данные от esia-mini
        $userData = $request->validate([
            'lastName' => 'required|string',
            'firstName' => 'required|string',
            'middleName' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'snils' => 'nullable|string',
            'birthDate' => 'nullable|date',
            'gender' => 'nullable|string',
        ]);

        // Сохраняем данные в сессию
        Session::put('esia_user', $userData);

        // Редиректим на форму виртуальной приемной
        return redirect()->route('virtual-reception.index');
    }

    /**
     * Сохранить жалобу
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lastName' => 'required|string|max:255',
            'firstName' => 'required|string|max:255',
            'middleName' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'snils' => 'nullable|string|max:14',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        // Добавляем данные ЕСИА
        $validated['esia_user_id'] = Session::get('esia_user.id');
        $validated['created_at'] = now();

        // Сохраняем в базу данных
        \DB::table('oms_virtual_reception')->insert($validated);

        return redirect()->back()->with('success', 'Ваше обращение успешно отправлено!');
    }
}
