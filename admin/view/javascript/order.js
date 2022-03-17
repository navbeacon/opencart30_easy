$(".nets-container").closest('.frame-wrapper').addClass('nets');
$(".nets-container").closest('.frame-wrapper').find(".frame-head").addClass('nets');
$("#nets-logo").html('<img href="./Images/nets-easy.png">');


$(function() {
	$("tr.lining").each(function(){
		var $quan = $(this).find(".quant").val();
		var $ref = $(this).find(".reference").val();
		var $qty = $(this).find(".single.qty").val();
		var $tep = $(this).find(".single.qty").attr('step');
		var $price = $(this).find(".grossprice").val();
		var $curr = $(this).find(".currency").val();
		var $minu = $(this).find(".minus");
		var $quty = $(this).find(".single.qty");
		var $plu = $(this).find(".plus");
		$(this).find('.priceblk').html(parseFloat($qty*$price).toFixed(2) + ' ' + $curr);
		if ($quan == $tep) { 
			$minu.addClass('is-disabled');
			$plu.addClass('is-disabled');
		}
		if ($quan == $qty) { 
			$plu.addClass('is-disabled');
		}
		if ($quan == 1) { 
			$minu.addClass('is-disabled');
			$quty.addClass('is-disabled');
			$plu.addClass('is-disabled');
		}
	});
});

$(function() {
	$("tr.listing").each(function(){
		var $quan = $(this).find(".quant").val();
		var $price = $(this).find(".grossprice").val();
		var $curr = $(this).find(".currency").val();
		$(this).find('.result').html(parseFloat($quan*$price).toFixed(2) + ' ' + $curr);
	});
});

$(".single.qty").on('input change', function(e) {
	var currentSingle = parseFloat($(this).val());
	var ref = $(this).closest('.quantity').find(".reference").val();
	var price = $(this).closest('.quantity').find(".grossprice").val();
	var currency = $(this).closest('.quantity').find(".currency").val();
	var $plus = $(this).closest('.quantity').find(".plus");
	var $minus = $(this).closest('.quantity').find(".minus");
	var min = parseFloat($(this).attr('min'));
	var max = parseFloat($(this).attr('max'));
	$('#price_'+ref).text(parseFloat(currentSingle*price).toFixed(2)  + ' ' + currency);

	if (!currentSingle || currentSingle == "" || currentSingle == "NaN") { 
		$(this).val(min); 
		$('#price_'+ref).text(parseFloat(min*price).toFixed(2) + ' ' + currency); 
		$minus.addClass('is-disabled'); 
	}
	if (min > currentSingle && e.keyCode !== 46  && e.keyCode !== 8) { 
		e.preventDefault(); 
		$(this).val(min); 
		$('#price_'+ref).text(parseFloat(min*price).toFixed(2) + ' ' + currency); 
		$minus.addClass('is-disabled'); 
	}
	if (currentSingle > max && e.keyCode !== 46  && e.keyCode !== 8) { 
		e.preventDefault(); 
		$(this).val(max); 
		$('#price_'+ref).text(parseFloat(max*price).toFixed(2) + ' ' + currency); 
		$plus.addClass('is-disabled'); 
	}
	if (currentSingle > min && currentSingle < max) { 
		e.preventDefault(); 
		$minus.removeClass('is-disabled'); 
		$plus.removeClass('is-disabled'); 
	}
});

$(".quantity").on('click', '.plus, .minus', function () {
	var ref = $(this).closest('.quantity').find(".reference").val().replace(/\s/g, "");
	var price = $(this).closest('.quantity').find(".grossprice").val();
	var currency = $(this).closest('.quantity').find(".currency").val();
	var $qty = $(this).closest('.quantity').find(".qty");
	var currentInput = parseFloat($qty.val());
	var min = parseFloat($qty.attr('min'));
	var max = parseFloat($qty.attr('max'));
	var minus = $(this).closest('.quantity').find(".minus");
	var plus = $(this).closest('.quantity').find(".plus");
	var step = $qty.attr('step');
	if ( $qty.val() == 1 && $(this).is('.minus') ) { minus.addClass('is-disabled'); return; }
	if (!currentInput || currentInput == "" || currentInput == "NaN") currentInput = 1;
	if (max == "" || max == "NaN") max = '';
	if (min == "" || min == "NaN") min = 1;
	if (step == 'any' || step == "" || step == undefined || parseFloat(step) == "NaN") step = 1;
	if ($(this).is('.plus')) {
		if ( max && currentInput  >= max ) {
			$qty.val(max);
			$('#item_'+ref).val(max);
			$('#price_'+ref).text(parseFloat(max*price).toFixed(2) + ' ' + currency);
			plus.addClass('is-disabled');
		} else {
			currentInput++;
			$qty.val(currentInput);
			$('#item_'+ref).val(currentInput);
			$('#price_'+ref).text(parseFloat(currentInput*price).toFixed(2) + ' ' + currency);
			minus.removeClass('is-disabled');
			plus.removeClass('is-disabled');
		}
	} else {
		if (min && (min == currentInput || currentInput < min)) {
			$qty.val(min);
			$('#item_'+ref).val(min);
			$('#price_'+ref).text(parseFloat(min*price).toFixed(2) + ' ' + currency);
			minus.addClass('is-disabled');
		} 
		else if (currentInput > 0) 
		{
			currentInput--;
			$qty.val(currentInput);
			$('#item_'+ref).val(currentInput);
			$('#price_'+ref).text(parseFloat(currentInput*price).toFixed(2) + ' ' + currency);
			minus.removeClass('is-disabled');
			plus.removeClass('is-disabled');
		}
	}
});


