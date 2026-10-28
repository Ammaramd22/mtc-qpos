{{--
  Legacy edit view — unused.
  SellPosController@edit renders sale_pos.create with $edit=true so
  Business Settings → POS UI (classic|touch) applies the same as create.
--}}
@php
    abort(404, 'POS edit uses the create screen. Open the sale again from Recent Transactions.');
@endphp
