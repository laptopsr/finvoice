

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script type="text/javascript" src="jquery.soap.js"></script>

<script type="text/javascript">
$(document).ready(function(){

$.soap({
    url: 'http://www.fkl.fi/teemasivut/finvoice',
    method: 'helloWorld',

    data: {
        name: 'Remy Blom',
        msg: 'Hi!'
    },

    success: function (soapResponse) {
        // do stuff with soapResponse
        // if you want to have the response as JSON use soapResponse.toJSON();
        // or soapResponse.toString() to get XML string
        // or soapResponse.toXML() to get XML DOM
    },
    error: function (SOAPResponse) {
        // show error
    }
});

});
</script>
