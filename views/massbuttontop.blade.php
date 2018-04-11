@if (count($dg->massCheckoutButtons) > 0)

    @foreach($dg->massCheckoutButtons as $button)

        {!! Form::open(array_merge(['class' => "mass-buttons", 'method' => $button->method], $button->getCustomFormAttributes())) !!}
            {!! $button->toHtml() !!}
        {!! Form::close() !!}

    @endforeach


    <br />
@else
@endif
