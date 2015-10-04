(function($){
    $(document).ready(function(){

        $('#_action_consulta_sinonims').click(function(){

            var url = '/sinonims/api/search';
            var query = $('#sinonims').val();

            $.ajax({
                url : url,
                type:"POST",
                data : {'format':'application/json','q':query},
                dataType: 'json',
                success: printSynsets,
                error: errorSynsets
            });

            return false;
        });

        function printSynsets(data) {
            var synsets = data.synsets;
            var query = $('#sinonims').val();

            var toAdd = '';

            if(synsets.length > 0) {
                toAdd = '<h1>'+query+'</h1><ol>';
                $(synsets).each(function() {
                    var categoria = this.categories[0];
                    if(categoria == "undefined")
                    {
                        categoria = "";
                    }

                    toAdd += '<li><strong>'+categoria+'</strong>: ';
                    toAdd += $.map(this.terms, printTerm).join(', ');
                })

                toAdd += '</ol>';
            } else {
                toAdd = '<br /><p>No hem trobat cap resultat al nostre diccionari</p>';
            }

            $('#results').html(toAdd);
            $('#results').slideDown();
        }

        function printTerm(term,index) {
            var ret = term.term;

            if(term.level) {
                ret += " ("+term.level+")";
            }

            return ret;
        }

        function errorSynsets() {

        }
    });

})(jQuery)
