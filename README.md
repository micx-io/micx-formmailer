# micx-formmailer
Ajax Formmailer

## Demo

- [Boostrap5 usage demo](www/demo/bootstrap5.html)

## Usage Example


| name-Attribute | type | description                                             |
|----------------|------|---------------------------------------------------------|
| mailto         | email | The alternative email address to send the form data to. |
| email          | email | The senders reply to address                            |
| name           | text  | The senders name                                        |


## Example config

```jaml
active: true
public:
  host_config: abc

private:
  templates:
    default:
      allow_mailto:
        - "*@@"
      mail_to: fallback@domain.de


```


```html
<form method="post">
    <micx-formmail
            onload="console.log('loaded')"
            onwaiting="this.show(`*[role='status']`)"
            onsubmit="this.hide(`button[type='submit'],*[role='data_invalid']`);this.show(`*[role='success']`)"
            oninvalid="this.show(`*[role='data_invalid']`)"
            onerror="alert('Couldnt send mail');"
            debug="yes"
    >
        <div class="alert alert-danger">The mailing function is currently not available!</div>
    </micx-formmail>
    <script src="http://localhost/formmail.js?subscription_id=demo"></script>
    
    <input type="text" name="userName1">
    <button type="submit">Send mail</button>
</form>
```




