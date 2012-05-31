
!function ($) {

$('.add-btn').live('click', function(event) {
	var btn = $(this)
	event.preventDefault()
	btn.button('loading')
	var tr = btn.closest('tr')
	var data = getData(tr)
	url = tr.attr('action')
	$.post(url, data, function(result) {
		console.debug(tr.find('[name="_dontInlineEdit"]'));
		tr.after(result)
		
		if(tr.find('[name="_dontInlineEdit"]').length>0) {
			var id = tr.next().find('[name="id"]').val();
			if(id) window.location=url+id;
		}
		btn.button('reset')
	})
})


$('.edit-btn').live('click', function() {
	var btn = $(this)
	btn.button('loading')
	
	var tr = btn.closest('tr')
	var id = tr.find('[name="id"]').val()
	url = tr.attr('source')
	
	$.post(url, "id="+id, function(result) {
		tr.after(result)
		tr.remove()
		btn.button('reset')
	})
})

$('.save-btn').live('click', function(event) {
	var btn = $(this)
	event.preventDefault()
	btn.button('loading')
	var tr = btn.closest('tr')
	var data = getData(tr)
	url = tr.attr('action')
	$.post(url, data, function(result) {
		tr.after(result)
		tr.remove()
		btn.button('reset')
//		addPopovers(); // why this doesn't work?
	})
})

// Get the row data for the submit variable from the row

function getData(tr) {

	var data = ''
	tr.find('input').each(function() {
		var name = $(this).attr('name')
		var value = $(this).attr('value')
		
		if(name.substring(name.length - 7)=='-list[]') {
			
			if($(this).attr('checked')=='checked') {
				data += name + "=" + value + "& "
			}
		}
		else {
			data += name + "=" + value + "& "
		}
		
	})
	data = data.substring(0, data.length - 2)
	return data
}

// Submit on pressing enter

$('tr.editableRow').live('keyup', function(event){
	if(event.keyCode == 13) $(this).find('.add-btn').click()
	if(event.keyCode == 13) $(this).find('.save-btn').click()
})


// Search Filtration


//$("tr.filtration input").change(function(){
//	
//	var url = $(this).closest('tr').attr('action');
//	
//	var where = getData($(this).closest('tr'));
//	var query = getData($('tr.pagination'));
//	
//	var request = {};
//	request.query = query;
//	request.where = where;
//	
//	$.post(url, request, function(result) {
//		console.debug(result);
//	})
//	
//});

$("tr.pagination a").live('click', function(event){
	event.preventDefault();
	
	var tr = $(this).closest('tr');
	var url = tr.attr('action');
	var data = getData($(this).closest('tr'));
	
	$.post(url, data, function(result) {
		console.debug(result);
		tr.parent().append(result);
		tr.remove();
	});
	
});


}(window.jQuery)