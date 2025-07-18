@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="mb-2 row">
                <div class="col-sm-6">
                    <h1>{{ __('Profile') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                                       href="{{ route('profile.index') }}">{{ __('Profile') }}</a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT HEADER -->

    <!-- MAIN CONTENT -->
    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="px-0 col-lg-12">
                    @if (!Auth::user()->hasVerifiedEmail() && $force_email_verification)
                        <div class="p-2 m-2 alert alert-warning">
                            <h5><i class="icon fas fa-exclamation-circle"></i>{{ __('Required Email verification!') }}
                            </h5>
                            {{ __('You have not yet verified your email address') }}
                            <a class="text-primary"
                               href="{{ route('verification.send') }}">{{ __('Click here to resend verification email') }}</a>
                            <br>
                            {{ __('Please contact support If you didnt receive your verification email.') }}

                        </div>
                    @endif

                    @if (is_null(Auth::user()->discordUser) && $force_discord_verification)
                        @if (!empty($discord_client_id) && !empty($discord_client_secret))
                            <div class="p-2 m-2 alert alert-warning">
                                <h5>
                                    <i class="icon fas fa-exclamation-circle"></i>{{ __('Required Discord verification!') }}
                                </h5>
                                {{ __('You have not yet verified your discord account') }}
                                <a class="text-primary"
                                   href="{{ route('auth.redirect') }}">{{ __('Login with discord') }}</a> <br>
                                {{ __('Please contact support If you face any issues.') }}
                            </div>
                        @else
                            <div class="p-2 m-2 alert alert-danger">
                                <h5>
                                    <i class="icon fas fa-exclamation-circle"></i>{{ __('Required Discord verification!') }}
                                </h5>
                                {{ __('Due to system settings you are required to verify your discord account!') }} <br>
                                {{ __('It looks like this hasnt been set-up correctly! Please contact support.') }}'
                            </div>
                        @endif
                    @endif

                </div>
            </div>

            <form class="form" action="{{ route('profile.update', Auth::user()->id) }}" method="post">
                @csrf
                @method('PATCH')
                <div class="card">
                    <div class="card-body">
                        <div class="e-profile">
                            <div class="row">
                                <div class="mb-4 col-12 col-sm-auto">
                                    <div class="border slim rounded-circle border-secondary text-gray-dark"
                                         data-label="Change your avatar" data-max-file-size="3"
                                         data-save-initial-image="true"
                                         style="width: 140px;height:140px; cursor: pointer"
                                         data-size="140,140">
                                        <img src="{{ $user->getAvatar() }}" alt="avatar">
                                    </div>
                                </div>
                                <div class="mb-3 col d-flex flex-column flex-sm-row justify-content-between">
                                    <div class="mb-2 text-center text-sm-left mb-sm-0">
                                        <h4 class="pb-1 mb-0 pt-sm-2 text-nowrap">{{ $user->name }}</h4>
                                        <p class="mb-0">{{ $user->email }}
                                            @if ($user->hasVerifiedEmail())
                                                <i data-toggle="popover" data-trigger="hover" data-content="Verified"
                                                   class="text-success fas fa-check-circle"></i>
                                            @else
                                                <i data-toggle="popover" data-trigger="hover"
                                                   data-content="Not verified"
                                                   class="text-danger fas fa-exclamation-circle"></i>
                                            @endif

                                        </p>
                                        <div class="mt-1">
                                            <span class="badge badge-primary"><i
                                                    class="mr-2 fa fa-coins"></i>{{ $user->Credits() }}</span>
                                        </div>

                                    @if($referral_enabled)
                                        @can("user.referral")
                                            <div class="mt-1">
                                                    <span class="badge badge-success"><i
                                                            class="mr-2 fa fa-user-check"></i>
                                                        {{__("Referral URL")}} :
                                                        <span onclick="onClickCopy()" id="RefLink" style="cursor: pointer;">
                                                            {{route("register")}}?ref={{$user->referral_code}}</span>
                                                    </span>
                                                @else
                                                    <span class="badge badge-warning"><i
                                                            class="mr-2 fa fa-user-check"></i>
                                                        {{__("You can not see your Referral Code")}}</span>
                                        @endcan
                                            </div>
                                        @endif
                                        </div>

                                        <div class="text-center text-sm-right">
                                            @foreach ($user->roles as $role)
                                                <span style='background-color: {{$role->color}}' class='badge'>{{$role->name}}</span>
                                            @endforeach
                                            <div class="text-muted">
                                                <small>{{ $user->created_at->isoFormat('LL') }}</small>
                                            </div>
                                            <div class="text-muted">
                                                <small>
                                                            <button class="badge badge-danger" id="confirmDeleteButton" type="button">{{ __('Permanently delete my account') }}</button>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <ul class="nav nav-tabs">
                                    <li class="nav-item"><a href="javasript:void(0)"
                                                            class="active nav-link">{{ __('Settings') }}</a>
                                    </li>
                                </ul>
                                <div class="pt-3 tab-content">
                                    <div class="tab-pane active">
                                        <div class="row">
                                            <div class="col">
                                                <div class="row">
                                                    <div class="col">
                                                        @if( $errors->has('phoenixpanel_error_message') )
                                                            @foreach( $errors->get('phoenixpanel_error_message') as $err )
                                                                <span class="text-danger" role="alert">
                                                                    <small><strong>{{ $err }}</strong></small>
                                                                </span>
                                                            @endforeach
                                                        @endif
                                                        @if( $errors->has('phoenixpanel_error_status') )
                                                            @foreach( $errors->get('phoenixpanel_error_status') as $err )
                                                                <span class="text-danger" role="alert">
                                                                        <small><strong>{{ $err }}</strong></small>
                                                                    </span>
                                                            @endforeach
                                                        @endif
                                                        <div class="form-group"><label>{{__('Name')}}</label> <input
                                                                class="form-control @error('name') is-invalid @enderror"
                                                                type="text" name="name" placeholder="{{ $user->name }}"
                                                                value="{{ $user->name }}">

                                                            @error('name')
                                                            <div class="invalid-feedback">
                                                                {{ $message }}
                                                            </div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="form-group"><label>{{ __('Email') }}</label> <input
                                                                class="form-control @error('email') is-invalid @enderror"
                                                                type="text" placeholder="{{ $user->email }}" name="email"
                                                                value="{{ $user->email }}">

                                                            @error('email')
                                                            <div class="invalid-feedback">
                                                                {{ $message }}
                                                            </div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="mb-3 col-12 col-sm-6">
                                                <div class="mb-3"><b>{{ __('Change Password') }}</b></div>
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="form-group">
                                                            <label>{{ __('Current Password') }}</label>
                                                            <input
                                                                class="form-control @error('current_password') is-invalid @enderror"
                                                                name="current_password" type="password"
                                                                placeholder="••••••">

                                                            @error('current_password')
                                                            <div class="invalid-feedback">
                                                                {{ $message }}
                                                            </div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="form-group"><label>{{ __('New Password') }}</label>
                                                            <input
                                                                class="form-control @error('new_password') is-invalid @enderror"
                                                                name="new_password" type="password" placeholder="••••••">

                                                            @error('new_password')
                                                            <div class="invalid-feedback">
                                                                {{ $message }}
                                                            </div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="form-group">
                                                            <label>{{ __('Confirm Password') }}</span></label>
                                                            <input
                                                                class="form-control @error('new_password_confirmation') is-invalid @enderror"
                                                                name="new_password_confirmation" type="password"
                                                                placeholder="••••••">

                                                            @error('new_password_confirmation')
                                                            <div class="invalid-feedback">
                                                                {{ $message }}
                                                            </div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @if (!empty($discord_client_id) && !empty($discord_client_secret))
                                                <div class="mb-3 col-12 col-sm-5 offset-sm-1">
                                                    @if (is_null(Auth::user()->discordUser))
                                                        <b>{{ __('Link your discord account!') }}</b>
                                                        <div class="verify-discord">
                                                            <div class="mb-3">
                                                                @if ($credits_reward_after_verify_discord)
                                                                    <p>{{ __('By verifying your discord account, you receive extra Credits and increased Server amounts') }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <a class="btn btn-light" href="{{ route('auth.redirect') }}">
                                                            <i class="mr-2 fab fa-discord"></i>{{ __('Login with Discord') }}
                                                        </a>
                                                    @else
                                                        <div class="verified-discord">
                                                            <div class="my-3 callout callout-info">
                                                                <p>{{ __('You are verified!') }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="pl-2 row">
                                                            <div class="small-box bg-dark">
                                                                <div class="d-flex justify-content-between">
                                                                    <div class="p-3">
                                                                        <h3>{{ $user->discordUser->username }}
                                                                            <sup>{{ $user->discordUser->locale }}</sup>
                                                                        </h3>
                                                                        <p>{{ $user->discordUser->id }}
                                                                        </p>
                                                                    </div>
                                                                    <div class="p-3"><img width="100px"
                                                                                          height="100px"
                                                                                          class="rounded-circle"
                                                                                          src="{{ $user->discordUser->getAvatar() }}"
                                                                                          alt="avatar"></div>
                                                                </div>
                                                                <div class="small-box-footer">
                                                                    <a href="{{ route('auth.redirect') }}">
                                                                        <i
                                                                            class="mr-1 fab fa-discord"></i>{{ __('Re-Sync Discord') }}
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                </div>
                                            @endif
                                        </div>
                                        <div class="row">
                                            <div class="col d-flex justify-content-end">
                                                <button class="btn btn-primary"
                                                        type="submit">{{ __('Save Changes') }}</button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                </form>

            </div>
            <!-- END CUSTOM CONTENT -->

            </div>
        </section>
        <!-- END CONTENT -->
    <script>
        document.getElementById("confirmDeleteButton").onclick=async ()=>{
                const {value: enterConfirm} = await Swal.fire({
                    input: 'text',
                    inputLabel: '{{__("Are you sure you want to permanently delete your account and all of your servers?")}} \n Type "{{__('Delete my account')}}" in the Box below',
                    inputPlaceholder: "{{__('Delete my account')}}",
                    showCancelButton: true
                })
                if (enterConfirm === "{{__('Delete my account')}}") {
                    Swal.fire("{{__('Account has been destroyed')}}", '', 'error')
                    $.ajax({
                        type: "POST",
                        url: "{{route("profile.selfDestroyUser")}}",
                        data: `{
                        "confirmed": "yes",
                      }`,
                        success: function (result) {
                            console.log(result);
                        },
                        dataType: "json"
                    });
                    location.reload();

                } else {
                    Swal.fire("{{__('Account was NOT deleted.')}}", '', 'info')

                }

            }
        function onClickCopy() {
            let textToCopy = document.getElementById('RefLink').innerText;
            if(navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("URL copied to clipboard")}}',
                        position: 'top-middle',
                        showConfirmButton: false,
                        background: '#343a40',
                        toast: false,
                        timer: 1000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    })
                })
            } else {
                console.log('Browser Not compatible')
            }
        }
    </script>
    @endsection
