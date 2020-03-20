# Extension:SimpleSAMLphp

## Define custom user info provider

If you want to modify any of the fields `username`, `realname` or `email` before login, you can
configure a custom callback for `$wgSimpleSAMLphp_MandatoryUserInfoProviderFactories`. The factory
method has the following signature:

    factoryCallback( \Config $config ) : MediaWiki\Extension\SimpleSAMLphp\IUserInfoProvider

For simple usecases one can use `MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\GenericCallback`:

    $wgSimpleSAMLphp_MandatoryUserInfoProviderFactories['username'] = function( $config ) {
        return new MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\GenericCallback( function( $attributes ) {
            if ( !isset( $attributes['mail'] ) ) {
                throw new Exception( 'missing email address' );
            }
            $parts = explode( '@', $attributes['mail'][0] );
            return strtolower( $parts[0] );
        } );
    };
