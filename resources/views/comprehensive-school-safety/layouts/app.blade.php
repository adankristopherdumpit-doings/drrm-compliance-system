<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Comprehensive School Safety')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/comprehensive-school-safety-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/comprehensive-school-safety-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/comprehensive-school-safety-logo.png') }}">

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --csss-primary: #5c4033;
            --csss-primary-dark: #3f2a21;
            --csss-primary-soft: #8b6f47;
            --csss-surface: #f4efe8;
            --csss-card: #ffffff;
            --csss-border: #e4d8c9;
            --csss-text: #2b221d;
            --csss-muted: #7a6a5f;
            --csss-sidebar: #1f1a17;
            --csss-sidebar-muted: #b9aba0;
        }

        body {
            margin: 0;
            background: radial-gradient(circle at top left, #f9f5ee, #f3ece2 40%, #efe7dc);
            color: var(--csss-text);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .csss-shell {
            min-height: 100vh;
            display: flex;
        }

        .csss-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2a211c 0%, var(--csss-sidebar) 100%);
            color: #fff;
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            height: 100vh;
            align-self: flex-start;
        }

        .csss-brand {
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            padding-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 50px;
        }

        .csss-brand h5 {
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.02em;
            font-size: 1rem;
        }

        .csss-brand small {
            color: var(--csss-sidebar-muted);
            display: block;
            font-size: 0.8rem;
        }

        .csss-menu-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #cdbfb2;
            margin-top: 0.5rem;
            margin-bottom: 0.35rem;
            letter-spacing: 0.08em;
        }

        .csss-menu-link {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.03);
            color: #f2e7da;
            border-radius: 12px;
            padding: 0.65rem 0.75rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.92rem;
            transition: all 0.2s ease;
        }

        .csss-menu-link:hover {
            background: rgba(255, 255, 255, 0.11);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.2);
        }

        .csss-menu-link.active {
            background: linear-gradient(135deg, var(--csss-primary) 0%, var(--csss-primary-soft) 100%);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 8px 20px rgba(92, 64, 51, 0.35);
        }

        .csss-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 100vh;
        }

        .csss-topbar {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--csss-border);
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .csss-topbar-side {
            width: 42px;
            min-width: 42px;
            display: inline-flex;
            justify-content: center;
        }

        .csss-topbar-title {
            flex: 1;
            text-align: center;
            min-width: 0;
        }

        .csss-search {
            max-width: 580px;
        }

        .csss-search .input-group-text,
        .csss-search .form-control {
            border-color: var(--csss-border);
            background: #fff;
        }

        .csss-notification {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid var(--csss-border);
            background: #fff;
            color: var(--csss-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .csss-content {
            padding: 1.5rem;
        }

        .csss-card {
            border: 1px solid var(--csss-border);
            background: rgba(255, 255, 255, 0.85);
            border-radius: 16px;
            box-shadow: 0 8px 22px rgba(92, 64, 51, 0.08);
        }

        .csss-section-title {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--csss-primary-dark);
        }

        .csss-muted {
            color: var(--csss-muted);
        }

        @media (max-width: 991.98px) {
            .csss-shell {
                display: block;
            }

            .csss-sidebar {
                width: 100%;
                border-radius: 0 0 14px 14px;
            }

            .csss-topbar {
                position: static;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
<div class="csss-shell">
    @php
        $selectedSchool = $school ?? null;
        $activeMenu = trim($__env->yieldContent('activeMenu'));
        $currentUser = auth()->user();
        $isAdmin = $currentUser && $currentUser->role === 'admin';
        $sessionSchoolId = session('csss_active_school_id');

        if (!$selectedSchool && $isAdmin && !empty($sessionSchoolId)) {
            $selectedSchool = \App\Models\School::find($sessionSchoolId);
        }

        $headerLabel = trim($__env->yieldContent('headerLabel'));

        if ($headerLabel === '') {
            $headerLabel = $selectedSchool ? $selectedSchool->name : 'All Schools';
        }

        $switchSchoolRouteByMenu = [
            'assessments' => 'comprehensive-school-safety.school.assessments',
            'facilities' => 'comprehensive-school-safety.school.facilities',
            'reports' => 'comprehensive-school-safety.school.reports',
            'storage' => 'comprehensive-school-safety.school.storage',
            'students' => 'comprehensive-school-safety.school.students',
        ];

        $switchSchoolRouteName = $switchSchoolRouteByMenu[$activeMenu] ?? 'comprehensive-school-safety.dashboard';

        if (!isset($directorySchoolsForComprehensiveRegistration)) {
            $directorySchoolsForComprehensiveRegistration = \App\Models\School::query()
                ->whereDoesntHave('specifics', function ($q) {
                    $q->where('module', 'comprehensive')->where('key', 'original_cmpr_school_id');
                })
                ->orderBy('school_name')
                ->get([
                    'id', 'school_name', 'school_id', 'school_id_number', 'address', 'school_head',
                    'drrm_coordinator', 'contact_number', 'district', 'division', 'region',
                ]);
        }
    @endphp

    <aside class="csss-sidebar">
        <div class="csss-brand d-flex align-items-center justify-content-between gap-2">
            <a href="{{ route('dashboard') }}" class="text-white text-decoration-none" title="Back to Main Dashboard" style="font-size: 1.2rem; flex-shrink: 0;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="d-flex align-items-center gap-2" style="flex: 1;">
                @if(file_exists(public_path('images/comprehensive-school-safety-logo.png')))
                    <img src="{{ asset('images/comprehensive-school-safety-logo.png') }}" alt="Comprehensive School Safety Logo" style="height: 40px; object-fit: contain; flex-shrink: 0;">
                @elseif(file_exists(public_path('images/comprehensive-school-safety-logo.jpg')))
                    <img src="{{ asset('images/comprehensive-school-safety-logo.jpg') }}" alt="Comprehensive School Safety Logo" style="height: 40px; object-fit: contain; flex-shrink: 0;">
                @endif
                <div style="min-width: 0;">
                    <h5 style="margin: 0; font-size: 0.95rem;">Comprehensive</h5>
                    <small style="color: var(--csss-sidebar-muted); display: block; font-size: 0.75rem;">School Safety</small>
                </div>
            </div>
        </div>

        @if($isAdmin)
            <div class="csss-menu-label">Dashboard</div>
            <a class="csss-menu-link {{ $activeMenu === 'dashboard' ? 'active' : '' }}"
               href="{{ route('comprehensive-school-safety.dashboard') }}">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
        @endif

        <div class="csss-menu-label">School Menu</div>

        @if(!$isAdmin)
            <a class="csss-menu-link {{ $activeMenu === 'dashboard' ? 'active' : '' }}"
               href="{{ $selectedSchool ? route('comprehensive-school-safety.school.dashboard', $selectedSchool->id) : route('comprehensive-school-safety.dashboard') }}">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
        @endif

        <a class="csss-menu-link {{ $activeMenu === 'assessments' ? 'active' : '' }}"
           href="{{ $selectedSchool ? route('comprehensive-school-safety.school.assessments', $selectedSchool->id) : route('comprehensive-school-safety.dashboard') }}">
            <i class="fas fa-list-check"></i> Assessments
        </a>

        <a class="csss-menu-link {{ $activeMenu === 'facilities' ? 'active' : '' }}"
           href="{{ $selectedSchool ? route('comprehensive-school-safety.school.facilities', $selectedSchool->id) : route('comprehensive-school-safety.dashboard') }}">
            <i class="fas fa-building"></i> Facilities
        </a>

        <div class="csss-menu-label">Reports Menu</div>
        <a class="csss-menu-link {{ $activeMenu === 'reports' ? 'active' : '' }}"
           href="{{ $selectedSchool ? route('comprehensive-school-safety.school.reports', $selectedSchool->id) : route('comprehensive-school-safety.dashboard') }}">
            <i class="fas fa-chart-pie"></i> Analytics &amp; Reports
        </a>

{{--
        <a class="csss-menu-link {{ $activeMenu === 'students' ? 'active' : '' }}"
           href="{{ $selectedSchool ? route('comprehensive-school-safety.school.students', $selectedSchool->id) : route('comprehensive-school-safety.dashboard') }}">
            <i class="fas fa-users"></i> Students
        </a>
--}}

        @if($isAdmin)
            <div class="mt-auto pt-3 border-top border-secondary-subtle">
                <button type="button" class="csss-menu-link w-100" data-bs-toggle="modal" data-bs-target="#switchSchoolModal">
                    <i class="fas fa-school"></i> Switch School
                </button>
            </div>
        @endif
    </aside>

    <div class="csss-main">
        <div class="csss-topbar d-flex align-items-center justify-content-between gap-3">
            <div class="csss-topbar-side"></div>
            <div class="csss-topbar-title">
                <div>
                    <h5 class="mb-0 fw-bold" style="color: var(--csss-primary-dark); font-size: 1.1rem;">
                        {{ $headerLabel }}
                    </h5>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown" style="display:flex; gap: .5rem; align-items:center;">
                    <button type="button" class="csss-notification" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ auth()->user()->name ?? 'Account' }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('users.index') }}">
                                <i class="fas fa-users-cog me-2"></i>User Accounts
                            </a>
                        </li>
                        @if($isAdmin)
                            <li>
                                <a class="dropdown-item" href="{{ route('activity-logs.index') }}">
                                    <i class="fas fa-clipboard-list me-2"></i>Logs
                                </a>
                            </li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Log Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <main class="csss-content">
            @yield('content')
        </main>
    </div>
</div>

@if($isAdmin)
    <!-- School Selection Modal -->
    <div class="modal fade" id="switchSchoolModal" tabindex="-1" role="dialog" aria-labelledby="switchSchoolLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0" style="border-radius: 14px;">
                <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, var(--csss-primary) 0%, var(--csss-primary-soft) 100%); border-radius: 14px 14px 0 0; color: white;">
                    <h5 class="modal-title fw-bold" id="switchSchoolLabel">
                        <i class="fas fa-school me-2"></i>Select a School
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    @php
                        $allSchools = \App\Models\School::query()
                            ->whereHas('specifics', function ($q) {
                                $q->where('module', 'comprehensive')->where('key', 'original_cmpr_school_id');
                            })
                            ->orderBy('school_name')
                            ->get();
                    @endphp
                    @if($allSchools->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-inbox text-muted" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
                            <p class="text-muted mb-3">No schools registered yet.</p>
                        </div>
                    @else
                        <div class="d-grid gap-2">
                            @foreach($allSchools as $switchSchool)
                                @php
                                    $isCurrentSwitchSchool = $selectedSchool && (int) $selectedSchool->id === (int) $switchSchool->id;
                                    $switchUrl = $switchSchoolRouteName === 'comprehensive-school-safety.dashboard'
                                        ? route('comprehensive-school-safety.dashboard', ['school_id' => $switchSchool->id])
                                        : route($switchSchoolRouteName, $switchSchool->id);
                                @endphp
                                <a href="{{ $switchUrl }}"
                                   class="btn text-start p-3 {{ $isCurrentSwitchSchool ? 'text-white' : 'btn-outline-secondary' }}"
                                   style="border-radius: 10px; transition: all 0.2s ease; {{ $isCurrentSwitchSchool ? 'background: var(--csss-primary); border-color: var(--csss-primary);' : '' }}">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="mb-1 fw-bold" style="color: {{ $isCurrentSwitchSchool ? '#ffffff' : 'var(--csss-primary)' }};">{{ $switchSchool->name }}</h6>
                                            <small style="color: {{ $isCurrentSwitchSchool ? 'rgba(255,255,255,0.9)' : 'var(--csss-muted)' }};">
                                                <i class="fas fa-map-marker-alt me-1"></i>{{ $switchSchool->district ?? 'N/A' }} • {{ $switchSchool->division ?? 'N/A' }}
                                            </small>
                                        </div>
                                        <i class="fas fa-chevron-right" style="margin-top: 5px; color: {{ $isCurrentSwitchSchool ? '#ffffff' : 'var(--csss-muted)' }};"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @endif

@stack('scripts')
</body>
</html>
