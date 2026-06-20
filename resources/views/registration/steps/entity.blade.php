@php
    $companyType = session('wizard_data.company_type', 'legal_entity');
@endphp

@if ($companyType === 'legal_entity')
    @include('registration::steps.entity-details')
@else
    @include('registration::steps.natural-person-details')
@endif
