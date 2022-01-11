<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
 * USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

declare(strict_types=1);

namespace Tests\Conjoon\Horde\Mail\Client\Imap;

use Conjoon\Mail\Client\Attachment\FileAttachment;
use Conjoon\Mail\Client\Attachment\FileAttachmentList;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Message\Flag\DraftFlag;
use Conjoon\Mail\Client\Message\Flag\FlagList;
use Horde_Imap_Client_Socket;
use Mockery;
use stdClass;
use Tests\TestCase;
use Tests\TestTrait;

/**
 * Class FilterTraitTest
 * @package Tests\Conjoon\Horde\Mail\Client\Imap
 */
class AttachmentTraitTest extends TestCase
{
    use TestTrait;
    use ClientGeneratorTrait;

    /**
     * Test getFileAttachmentList()
     *
     */
    public function testGetFileAttachmentList()
    {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $mailFolderId = "INBOX";
        $messageItemId = "123";

        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);

        $structureMock = new class () {
            public function first()
            {
                // @codingStandardsIgnoreStart
                return unserialize('O:28:"Horde_Imap_Client_Data_Fetch":1:{s:8:" * _data";a:3:{i:14;i:129616;i:13;i:181317;i:1;C:15:"Horde_Mime_Part":1322:{a:10:{i:0;i:2;i:1;N;i:2;s:1:"
";i:3;N;i:4;C:18:"Horde_Mime_Headers":490:{a:3:{i:0;i:3;i:1;a:2:{s:19:"Content-Disposition";C:50:"Horde_Mime_Headers_ContentParam_ContentDisposition":96:{a:3:{s:7:"_params";a:0:{}s:5:"_name";s:19:"Content-Disposition";s:7:"_values";a:1:{i:0;s:0:"";}}}s:12:"Content-Type";C:43:"Horde_Mime_Headers_ContentParam_ContentType":191:{a:3:{s:7:"_params";a:2:{s:7:"charset";s:8:"us-ascii";s:8:"boundary";s:34:"=_c50d6e38186c3c1d278ceaa331f26de5";}s:5:"_name";s:12:"Content-Type";s:7:"_values";a:1:{i:0;s:15:"multipart/mixed";}}}}i:2;s:1:"
";}}i:5;a:0:{}i:6;s:1:"0";i:7;a:1:{i:0;C:15:"Horde_Mime_Part":667:{a:10:{i:0;i:2;i:1;s:5:"21884";i:2;s:1:"
";i:3;N;i:4;C:18:"Horde_Mime_Headers":525:{a:3:{i:0;i:3;i:1;a:2:{s:19:"Content-Disposition";C:50:"Horde_Mime_Headers_ContentParam_ContentDisposition":164:{a:3:{s:7:"_params";a:2:{s:4:"size";s:5:"21884";s:8:"filename";s:11:"newmail.png";}s:5:"_name";s:19:"Content-Disposition";s:7:"_values";a:1:{i:0;s:10:"attachment";}}}s:12:"Content-Type";C:43:"Horde_Mime_Headers_ContentParam_ContentType":157:{a:3:{s:7:"_params";a:2:{s:7:"charset";s:8:"us-ascii";s:4:"name";s:11:"newmail.png";}s:5:"_name";s:12:"Content-Type";s:7:"_values";a:1:{i:0;s:9:"image/png";}}}}i:2;s:1:"
";}}i:5;a:0:{}i:6;s:1:"1";i:7;a:0:{}i:8;i:0;i:9;s:6:"base64";}}}i:8;i:0;i:9;s:6:"binary";}}}}');
                // @codingStandardsIgnoreEnd
            }
        };

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $bodyResultMock = new class () {
            public function first()
            {
                // @codingStandardsIgnoreStart
                return unserialize('O:28:"Horde_Imap_Client_Data_Fetch":1:{s:8:" * _data";a:3:{i:14;i:129616;i:13;i:181317;i:6;a:1:{i:1;a:2:{s:1:"d";N;s:1:"t";C:17:"Horde_Stream_Temp":31134:{["iVBORw0KGgoAAAANSUhEUgAAAEYAAABGCAYAAABxLuKEAAA263pUWHRSYXcgcHJvZmlsZSB0eXBl\r\nIGV4aWYAAHja3ZxZkmXJjWT\/bRW1BJuH5dgo0jvo5fdRXI+ozGSyipSqr2Ywwz3c37vPBkChCgPM\r\n3f\/7f577j\/\/4jxBHji6X1uuo1fO\/PPKIk2+6\/\/437e\/gs\/1t\/8v153fhzz93v38R+VHia\/r+2X9+\r\nHn79PPx+wPdl8l35w4P6\/vnF+vMvRv55fv\/Lg+L3JWlE+v78PGj8PCjF7xfh5wHzm5avo7c\/TmHd\r\n7+vP+79l4D+nv3L\/87D\/4d+N1TuFz0kx3hSS5++Y8jeApP+CS5NfBP72KfNCnwbfZ341U0rt52Es\r\nyN+tk\/\/DqNxfd+X3d+Gf\/Pwvm5Lq93PHD\/68mPX317\/9eSh\/+fnPA50t8R8+Oe3fn\/ynn+eVwl+n\r\n8+u\/9053791vdjNXlrT+TOrXVOw7Xrh4VLK3Vf40\/it83+zP4E93WO9my4\/ffvFnhxEi2\/JCDifM\r\n8MK1rztshpjjjY2vMW42Sj\/rqcURd\/KOHcv6E15s7NhJnR3bbG\/S3v0eS7DPHfZxO3Q++AReGQMP\r\nC7wjOv31v\/Hnnz7oPZl8CL7\/XivGFeUUDEM7p795FRsS3i87KrbAv\/789X\/a18QOFlvmzgSnX98j\r\nVgk\/tiU7SrbRiRcWvn6+Ftr5eQBLxGcXBoMH5OBrSCXU4FuMLQTWsbM\/kwd1nCYutiCUEg+jjDml\r\nyub0qM\/mPS3Ya2OJ34\/BLDaipJoaW4NLsVcZYMN+Wu7Y0Cyp5FJKLa30MsqsqeZaaq2tCvxmSy23\r\n0mprrbfRZk8999Jrb727PvoccSTAsYw62uhjjDn50MmTJ++evGDOFVdaeZVVV1t9jTU35rPzLrvu\r\ntrvbY88TTzrgxKmnnX7GmTdcTOnmW2697fY77nyY2ksvv\/Lqa6+\/8ebvXQvu29Z\/+POv71r4tWvR\r\ndkovbL93jbe29usRQXBStGfsWMyBHW\/aAQw6as98D5kooq3TnvkR8YoSGWXR5pygHWMH8w2xvPB7\r\n7\/5z5\/60by7n\/9G+xV8757R1\/xs757R1\/2Tn\/nHf\/mbXjqLN9snZDskNtag+4X684PYZ+1RQ+8ev\r\n44VdYj7R9xszk0wnsDguj7cVBAe\/ZOtO85cd6uMClgkkY1lbeL3Ue3u5oa598rxj1Ft3fTP1tlfc\r\n87oVQmB1GBDbFu4qt7M1r\/uVwbThC5hZXynnxsOSsPRYBVv1znmVJ\/KoMkdpjg3DilPgc\/q9r6TN\r\nRwGRa85y39CCsjbxjH5X3RsU3Y8BtlzqeZ6hRI1sHTdqfGOFlwoAvR+ParPs1tgsdrCl2cua97F2\r\n9+3NPjZ9P+\/y862tSBDb3S+6w56\/yLj2GbeNmzoDPo\/xePb9lXD2S+mtnIAZ9pJAs5lobNtjEQSv\r\nUp4sz72xV2U0Z52M953LzJnHxKcvixRWYUGwgTWib6\/Mm7EBLXPfB3MYCZbBb6ILZZ3G++deAwtZ\r\nc7xWMZsQFlMKWRPpJz6eHPgd\/\/I3sXY8NO\/K2Crv5InuRUIQLpby6bxzYtnbs6Zx9scexn4qXKQQ\r\ne5gtbz\/v2cMDW+DbYqD5ztCmu60rnNe4W8Q7Xh24QM6sSpjEkj7rfrWmua\/FQXxxRT1ovYvhAt\/p\r\nXN4T3cMt8I4RsNNSMA9MrnVWlh+VNMvZI7zsmX\/afWrVWc0bWc1OiHwbfMcT83L4N1Y+PIuzMYKW\r\nQZT25uHn81xf8qnEMrnsnG\/fgOP6i5V7Zv4az+ffdb3mgL1zAKTY6sv7W84wD+TjhXXhX2+vm8+s\r\nIiqjYOR6ReOFfJY\/zKH39Yj9WExNcdQFYDzWaIQsb8f3dk7jjIYn8cKKCWBLODQbfPnQ9xk\/mNOw\r\n+PlcLOeNO7D+FPoGHQ5TLv3BdvfN7RDOVmQaeQ95wMb3gc+yFm+sWMHquwKl1x3msAZjuuD5nVP2\r\nMcHlcsfCocGb0MG4e4pw9bY1ph\/zHPz3BCxzA6++lehkXgF\/XafjXyA2e9leDKuHeO4CI8sqGb+Y\r\nOYD17a2FP1724+K7U\/a3Zins2mJNB9uJlSSANOfrb2a8GbOcpREKdgQacF0sd6ebAI2KX4LfJxcw\r\ntM\/ca4Zobdym8\/spCiUMS3EBGjilAaP\/l75e15csvlYAgh0AGDfes\/BShofztrIfOLcKaBpB8BMi\r\n2NJ3aCmzp0Hhh5\/JjvIgVvV43j49b7hhBRD4DUEMoz7axUrseYy2CqDi2cSDcdi2XMHANjarERwg\r\nVgkKqbCuvAGrPMdraRtRBxtcFQGwFT0b8fhkBlfzZcVxRMIueNMxiQyJwNgJq5MPB9ZmfpWdJ3J6\r\nnNyDQgAsbsW3k7cuplfAQ\/9WM6TsxM7DAOdws0BvAma+L7gwVz27HoZc23iwKbaBFUseT8Z0sOAF\r\n590JC2kxgfGJXz\/CSnOv4DAV04g47tkbUKiZIMFEriyAASbjUoJv6KE2u0gsvZeYMNstNFjBzQGe\r\nDYA3EmsDdn0BshLAYqKSZsb\/XjqlXTjCuNhhvdqPFStQfHBBT8z01Q25Xergfzhn1AMidI\/hQjnZ\r\nngnswTGP1qMyFoCmnwXIN0bSTlwFS4W7t+wI6lWf0YkVF56RMINFsNgNfhMIWMQRbHQfpHQ7+LDF\r\nJiITpG4DAWmuPQmi7uLMgwihiIYPCCUOaMtghVG8cfPRrd23sKQ55eoP6MMMwQS4Rj2TTQFqUayl\r\nYAd3EWgPTs7QYHW3EwsaKFthdxtbwoYF0XN2aNsAIx82y\/KuDpwuSAT4xysUKQJQnTJ7vTdoql1h\r\naFOrc55xLyYA8qRblxaRbUjP8LTAD7KDpcT69mCBDpQpsTFsOsuIZ4GihdhuTszEI\/wQfAirdUCG\r\nbYHEAbgINtbS4e5ogQtoEgoedlsWLIJQXeXKwkrQMwHlk7UVe9oK0MOGV54+vgt4tdiHSIt9gFEN\r\nSI3MkGeyFPkAr7Dcej0QxwDxiqu4oFdhEV02vngxa4AWYa3Y5YLDszy7XU+kt+E1Gx7Lt2+Pd6H2\r\nGpq0toUXMq1lDCUQsAmEDykqRp9CzYpIVTQCMvEF5cZmE+qUEyhRW3Im04AfYfgfI8Ajv\/DlZZCn\r\nZNnDlD0QYEaLBbzyMgccDx+8gZkO+CxuW6Ltd4IZ3Z\/thrjDBBtE64yXRGv65x+B3\/uUN9CEX53X\r\nGvSSIMpS4leVAddoXoGbLJwCiSCncFBxmNqQB+OG6M9BMNigxcx+9I2RYgrsDu7LQi75DYEa9Nus\r\nMBsXCW38pmBHN0B3kAGldSA24Hbr8Cw+x\/PkszsRiIASxS+gfCBBypOYj7W3cWB5YNvq7ohhwYfY\r\nmqU1keMMuDsYumDHO0dZCZao18iFPHRmEiEJoCJIhMHNlkbXYBawA1jNjQMvL8SwjBFOQR+2Fdgb\r\nYRMfxwsXc6ysD0aFr+DqPgMkCj2uBfgNtjkKhAHDYxcG0hhwIvYWBahJPCr3glrMCDzkpR4zxH4A\r\nJ3h886nH5kRz2X9AuRKIEfoE\/XGxgILwIt4Xn7C0g17gnURvRIZmBd+F314N9\/HQKFaLWCBu+AfQ\r\nNKyJGMZ\/ncCIT2Dr6UQ+o8ryCdSEvfs+Soo9V9B4EtDTDm5AIAaR9bHCZQJBY0AxYI0HQTFFGKDJ\r\noHHE49fCzxpaEmrMRAsRECiDucTinX2H\/PzjV9D\/+g1cinSizzo4hEtvzKKCDti4sG8KtSFCWAH0\r\n3U1EHoInEvkQHKw8pAS+BiyxjAjGMwbhk3XxGWND2gVMtA3WCG4K0CJVDV7cTBm3r8R29mB7WTCz\r\nRE4RmqoEqcwak8MniekMpjxMHfyA6GNhC+Ds9iDggp9jYG9j6\/i24YQ2Jfbqb2htbsbneQKmDJ8v\r\nUC+FvyJ+TFzYRNg2pLIrVk\/0CwtuhpsTOTzyZnkiMhjLyyueusDKvRHMcAYf7wZqLr6UYQZQHoI4\r\n1O\/hWUTpkJgaRAE+MzUkvyvxkCUK91t0PLLLA2AouxXsC99DUh+IFdLZ4TRSZ5CrjImKSRVFccLf\r\n82hm+CWEALxAQBCjGM3yqI204AkNlUEUZL1ZNhfh3Y3Z9U68Z1Ngc7JFtAGaYn0GT3Dn0zKLKTBu\r\nIBgcC4GHF8EHRFpucQDqNdUNNmsD4Gp5VvgUvA1+pvc3iSYIIVuIOaIeCdfmACLl90QAM0Nr8PBE\r\nwDQURsbhr5g5S9OQVk8A2PxM2DnWRMiQO2QIDsBGQOI1C06yUH\/OHxmqbHRF2KmYU41xlky45\/0N\r\nFQ03zqXAXbFtQnFCxQCNxPPOhmAF2OnbDhkgbyFyRnMSqfHfXyXXBgI4Hq91Rwl09DFB42PRyikw\r\nBPlLwkXOzLtAQH6NEsnPIAOTIbwT55\/NuCpiHAZSwmB34Fp+8qJ28HTUd3eIMsCBuaRGSE8DFgnb\r\ngDOw4eAvSoBPLA18F1byyuaRVxVHbmJB4fKWC7K6jIsmAk7Cunqoh4UaIWQoBqJPS82Q0BEXComx\r\nGTcByLo2GPG4Z9QGY8mSEOywFD50uLIoyJaK7IG\/El3Yz1IDgY8nsmwNAGozeAlDHo4gLBIL6O3k\r\nDryCDYqIqi1nPPv6msuYG7HOwsZQmhJnfCsmPi8ORtDCCLF7T6h4eCwo7JiNF8gS67t80cweW3nT\r\nS3LjB++Ku+KORZwV6gG\/exJYqD\/5bBSp9g6fPReQKvJZSJN89uJuqHm2Ci9cBJSMUGYJCias\/JhY\r\nQsX9QGlIp\/JuD7mu9EmbuXpkLy7GZmJe+RhK7shyR4OAUEHJnDFD1HgSSnYFvlgRAfjedUgK1Dzw\r\nT2x5ovEa1xHDRBUxFtxlmuNspDPiD0XDJ4icFeI3HJctlLp1EfSYDDoqmwEaosbZ68PbrvQcGkUB\r\n1Ag7TCZ4NGS1tNLZCQLC3AiWTNYBumkp6CKAI0EU1CfcY7Qlsvj7y64Buz\/ptVxRsVOano++W9m1\r\nNgexz3XQrGG+EVqIfFHyuRKI+WSxnKG05QE9d9hB0EtcR+vNipjronJLxP+xgA79xsb1+JQPxd6A\r\nObHM9CBuBH\/4cZFOyMRK5hagRj0o3aLgje8z+Q5hjt2xtod1igx\/Kph4DZRVYdrIlYeu4h+XSI4s\r\n07hqKQP5Bw+EJNiia5oPp0UFDUwVPq\/scMU0Fq\/EiUfM\/mwPKW5KYsAyI9uG1eEQcS8AYqDNsChG\r\nDz2ueC9AGLaCBpMhyntsj3ebBaAvg9joIf6esJSvUbiEizZpIPBBaDMURRANcc6iYMKwcUdRUSCR\r\nKD9OxIZXIfBuYJx3zM4ialUHog7dhIrFQwkKyWWWXBlQZBCLJU9UCIJ1w1\/SOAofZ3Ze1BBWKYNS\r\nzLEQgVmq0\/tGGBEmxnE8VhB3sWxwASp7rw4tUmD+VUyyCMlr0VqMxdojAHklo7t6Gty4GUFGrgcd\r\nXyB92B3eTyQXscFaF5j2d6Tn77+6n28y8ZcHI3tEYGvIyCyI4JrQ4O\/hcGVsGCNDH3XY2iyKwbhV\r\ngJLXzdQiAhAnf2tYKFw8NELSAPXQP02CK5gIwy4gxKhKcWXWKVXoNosbJY79RDsiyibSO6Fib0Ku\r\n4vAADtEU2RaV2YhKDjE4NLTQ6PnVIf3lsjXsBm7LYoc8A4\/FblE+RACwi\/GwFcSyFSrxGVmGPBrY\r\nEMyTZ1zlxeDpCQG\/PRuM7U1X+no6zkBiwvYOIAx\/NhPMT5ok8SDtpygpyp2APm2fiWfpA3a\/ISDF\r\n4YTg9dhYMnJIxDryD7+QV2w4gZYgBJobyCiHBZwPc\/7F\/PFn3PmhLqObkM7QUwNrcX4AVNs3M6+G\r\nfBP4\/JLD9SrKz\/LhvN03on1AHgnxeCuWnzxRhCHw7iyeT+wpQIwngrGkF1FLwH8d6YFy8WzbXi1L\r\nwPENA2pTB6NFYpc1apk4zJJBGbCDhOBmHbzS0p43wJ0KzjJMCrfLiNgB1hkCWyLoR7hTBL0dVquk\r\nPEGuIZbKgTJASQFVWDci617xGUAuihMSVzv6hTFAwhg0mglEhc2iClzHtSL+zlcfAIaYBaiwXs8T\r\nEEgVXx2r433CotCwiT08O\/9wZR1DbB0TEmlLiATQgCNjsJmwNKsyXU2yAbAhHuQwgDIhaVgrjBqg\r\n9MWUHByHHUJj+FGdiDdowQbbObaXmEby+Bc6TJe3eEg2Ci9V6SGdNAxk6SC4AQyHn4WllzTXR86w\r\nbICl7QdnFrfGAKQkRZrbUlTCRDE1Nn5GiBnoAaGXgrhZbHfgdc\/5x3ZclnqUfiDCEYEeBX\/+ZqQp\r\nFGuXBI\/HWLB0JGFDrVzp8asM64G66bxhQthz63JUtD3bGANhXgftklE6IPn4tsIPccMUZGzsyFbC\r\nA0lQZjPBMJ20s4TOeEdsr2NJtUUgHq8EJVqJE6WktHpWChjIh0L1mFlXgC\/IIo2bupKTWO3N4RHo\r\nDPKy8BsHvzB3Pm5iKsApYWbMDF1VRQGWUeTb7KAx9HHd8DrrQGxhW0bac1M6TkzHUint6IvkdT8s\r\nbW4mR4nnkn3v\/bwcvfbzju\/1HpL\/p8\/508eADJD1vzzp10e7f\/js\/3zSv\/Kc\/19HNKT7CJaxhECA\r\nBBRiSiIWcOdg2eKFmWJaWGdkoH0Dn2N7AVL6HeNWEuLACdZUVHdvH6izdDY4qWOmicFmWWnG4XAj\r\nqOzY+1UADX0dEMC3enk6ZqlE3VW6tRS3sPa0AkY\/xFcw+gzxEQNFusFLb7zKgTxsERTImTnB6YiY\r\nvK4oqfmCwPs5yCQOMzMeDG49nSlNJFQBDjPUHHr8NCm0M4wn6EQgR4JcrkRLPs17pK7v2rViMM\/n\r\nBqLLQeQRLFIKA8klBgwDYAnShZMre1c6shNnHwQOxpVALaQP\/NrFpGhGGEo61UUVFChyaDCYxF4t\r\nw7EUDMcS0QChOVEEsyxLweCzhEJiAjCCXG3w4VXeYMjoByKF0N8mQexDKAn9lf7DuRF8EXACCC8W\r\nhOJuU6dOu7kliH8X3AvQSx8BnuNb3GcHiUFeLNoQAddgh\/PYBCwTnnZBavRq9EoP7AL4Q8cKymwy\r\nGUWAk4eU9tJBOHrhLImlxFyhAQuV2ZFT7LlWTLmaJcFCuHdRJz8dfnQJPWj00ZWY1MkqKq8pEiIa\r\nZFtBeW6QvcM6YffKDhFFjs5MdKLiNi9UCo+nw\/fDbdor1AwSC9XGLJVbhthsZQaqalhGg0kwHInW\r\nDPoNgudZjlCl1IVShYzK55XPgNbhkPsifI4EECNjiDpT929WrKkOWVOXZQDMyIx1nFeS5xLnm53m\r\ngNg6wQfsxabFZzQW0WUPD2b3bUAzJR2lHx1oJRx8r+LSCMoQfIqMWAInluMtAidih0EPyH4hem1l\r\nN4s2oTflKHWgT\/ysYMVhXVzu209YFotMLIEYR6UTdD4YdreVkiYbSl\/YCR97i9v15yGa7axUVLnA\r\nk9D9yFUCUzrf2eqqtQ90CqSMDWPHkPxQscJfyB0+Mxblt\/B\/ha2tnHyVtgQhmWtbsJfh0RCBMC+A\r\ns4PvqDTtxmRhDk\/1FIzgtqS8Byy8+AtntoRMq8HB+\/5Feh\/6YtYHKsasKmrtsTM6pzsaLSq7K7sB\r\nn8DhAE9WXTJq\/hLB4kIygTUAYODOEmG40kYT5AudBMOk8R3skKlOJA\/rmiF4KOIFcdN5vI5pKtRQ\r\nlQVZvBfVvNjXAJ1hKaclrjAtoB5ggxuBIjrxOFJQlty8jNz2vkt6+j\/ufROtYoJFkqf87L20SF5i\r\n+fweW+7K3GG0JaKo0BcFggQT3FgsnI6FwbgvcvammnOXGGV5ANinJAsLH5QdfG0ECVXEK5x4ICdZ\r\nC7z+2f5pLCqKaR69XgoAoJKakbW2A\/Nv3jUdy8EBkYz+LCjpwmV4I14Bx1aWDnsD0RELbeMgvF2e\r\nH1nGgkoFKwAakM7hARuRjvujjqBVlrMCygAX4SFRbZWt9A6GqSqPBHiu1UGUw5wk6tH07TwHk0NJ\r\nif2gjafIsupLjmIMcC5JBh1eoBrYBCVFY4Mh8NnJ6hTVKB1lL2YHai0Pcy4MbyphWvCl5+MdKaDO\r\nEEP5SvHrlDuiHuDpRh0kpgpYmiCZBMbiel+YvpkP26zDTFg4UZiwpIQnCnspV8hmJVYBCCdYpmO7\r\nRQCFemOtO2fv0KQ4eR0EPPSyiD40YqEwPWtxCO1zwvFWzkWnaOxMxlE8M60+x4BVY8NvoSAlP3YG\r\nsLxiYHirFSg7k\/GqUIgxE0qxPAV\/4hbRm4C6D+olENkYoaqEjNYAlEr4Z0QF2ugSZog7ONNF+b+8\r\n93ckF7wdqneo7HsSY3iu7KPo1JqgvI+TRpxsXR6js\/GI8xrlEyNJKFoKAAI6f+VvWQNUimpwQNBl\r\nH4N+x7hcxj\/gD1tOozMDLE1iHMqRCZZ8wo2pEHB9GIwhSWp7nXtZAUrVYTISKO7giK5o9S709xga\r\nWwLVaCy9TtMmsZ\/56KgxeAQ4+vCBdOwl4k8BWXOBbhGDHLFbR4yq\/ELeLa13H6qtqCaPkg5HUsZ0\r\ns6o7IDt8Ur4tYF1YLkRoM975hjuIa6XyD\/HiCb3goN2KCojexEkNE63ndVoaDtYJGWovsAgwKmgj\r\nDIQ5vOQWxAL6SCADyQiEU7WlEhW5pX8OvpDOKbXbJ6BQidhEEajmSIypxFa7jtwGrotNbZ28IPYh\r\nIAmyI+BUVgIvYeSoS0ISWltpTZ0iDKeYyG+PYirrghbCXglfYNnxYJg8F1FcJNtYVfwwqAJmKekW\r\n0HlwuKtw65pohyBM9WSwhg01SpEnxemz\/0p6JcEtlQPbalspKgWmwzLz+Q87wYzdS91vFdnqnPjq\r\nEEfYAQ0R28MQVG9DqJjHd0yoBeQmivyKD2y8Zk6UJNzqOJ1kQgLxJzDyKFfAXEEssK58MRpqRXQZ\r\nKuw7OF7Ye3oQ5vSk3BLkRFPojljyagb3AQwJcDASy8QlS80q\/ZrqNeiXqCvLYGVZrFN1xNo7g8cm\r\nIkS3RafT+8sYBuhOMC46FoDbQZXBHzgAM+yfSgEmcBP5fls\/vs8Gf77fsgM0CVLM0\/fxDPtU583m\r\nsI6AwZqG4kmlmD2qzG+ciuQ+K2OjOgcwrtCG0+ErvEuHLBOhCQtdRqL6wMm6juvZn6y9VVohI\/UL\r\n4C8+AS\/tH43RyYQjmBdROXlQ3ToFP1XVAZANwFChe29lsPW5ZQHiMDuAOFcsG+CE\/aytAzOXy1FZ\r\nlg0wN52D8UJEj0jM23uwKRZ\/sAyl5wcgLTxlad8Q+n+BIBV3E2xJCbepsxJCSUf\/YHwsvA6yx7p+\r\n48Q3gi0l1qBSCaiazrqUBlCxSN7QGMcQ8CXF7a2yDaPOr1vhyoYNVZQE+lLlHkE+gP5HXxDBIHcF\r\nWQMpJjCV4N0akByZPqQN17FKt6QT6RCwE3Sin0qIqCqnfOW2+Ppr+1epLbAkgv+cZ+R2zAAqMKID\r\nDdDhd8CZ80odKqwKCISO38nyhvhBsGPxP7\/P2Run6iou6CqZrLhQEMvX6gzvxZEw1\/iYO\/7BT1WA\r\np+PcpyoHnl9VweiOHoGmJIymoNoCOMNU4RzLMQmpmZ\/iFNg6SNXh9CrlDDrS1NkhWzCLlBaiBnID\r\naYELs2Uq7oU0YFEIhIA5elU3oE+RocRJwFlJXjBBsQRkY1JAOjIYPMrwPggfLgpNGBAOnfJezUlr\r\nQgw7Wl54KOY5lDG90\/MT9GDJGmOBeHQrrmAoU2m1JnIEz0Mc4GJs11CdMEQCkIUQgBAB8jEk4zTf\r\nRERja+\/A8TRi5xGXyGjpv4UA799Z3Z59qrp4dx2pVOKr8nfpq6tG+6N8iKEJPMQCitJ2TkWgdmwA\r\nDilOp7XRRUqxXU+Y5WlBMkbRizdAHVExKGMVOp2dCXzVQxXbdEAOHq5qBdjkUZL7ysmIdciarBQA\r\nMxiqwBejJYx5uCo7BJyAdZjzvCrz306Zd\/F5nLBoXVVAB3jLHVLMwDV4CTbWEYpaZboPOLbfHidQ\r\nhYxCbawEIodOXIBqVWr3QI+xKdF0pa61BVkpD38ZLKsTkpf2xsViqNa3AQNvROUz0P3IS5RcJiQV\r\nFS0QPLHghLk0FU0OZUeUydX5ZO9zWmEjOqhfKYyFSLEEkXdHjLgo+WnRGr1WrCAPULC6qFe2VcGw\r\neIhxcxqCghp\/moqQUVpH8D4dBJTdksDc2PNv91ad91cSyjZKrUSUgDSPkjplQOgwvKlFgkzLPd1m\r\n2MSjrSYaIsOE1io13CRfLK+MOnmhwz2xAayEpQ7AMSFb5eQY6exKkBWn8zjc1gYtLca\/VYbzoAFN\r\nxXqEzhQWwbpC5Ymq2ALfwsAvzp1vVl5CZ90OL6v8Ck40G5ocL+loFxCxifo8dU+0qLNHgD5AfcJJ\r\nrJbwur8PrwP6uFY3CeXKb2nTY+HDZGpneh1sqbyFf7INyg7wch24o1QkjVXvMqOqSz8V7GZadpCs\r\nKAnTYnQM2WP3R+X0WVmrBcNUOdhRGLv1O8QI\/Reor\/PxbItYf0D2Lj8Rsg8\/kxd+w5oJ\/s+AHcxR\r\n2jqWrxgjHMBpFNX6Vaul3xgDQhBoYZuI15EhgYU4OHNAR1tcuH+OC83iQrO44K\/754FBlYb95\/j5\r\nQ3i1CP4V4pXNFsgjIVb75N4bKsq10Iyw+ias3AL4y6e2Kdy7vxMLSb5wJNyWRpkcBpF4vRFZov7w\r\n8ni0PGRCzLdJX0KIlZpEoy9xIt96lgcOHUxViKHOmB0omOvwIalMbXb2rgMWe6pdgx9VldbeMAMh\r\nGCJ5Si86oNgqmgjAf1oCknwlapaOcRbEC1dD9RPLB0pP7Y0YBpQH7k6AauJQ86LSBoZ\/VJpVYIfE\r\n\/aWeleYyHAVgw4HEZhqfyTZPnW3Fu2HC0cPrVHCuHBJLU+GcQweYBQw9TWL1qTnJEb5uRhCF63FI\r\nmIn6Ra7OPORq4oMECXSJzGOhY\/b8+kWUvj4orTdRirirg7M0nTnArXEBJok4h1vr6GLMBeNUccjZ\r\nuxBuUoLpBFUXSOXwWjvKVmZV2WMJOVCa12ZUPSHgqjnGkk1qTGIbtmpMGgidtV2wZ0\/0Wyo2jBmN\r\njtpKqzoYBhyLz08TLdo7JHAj82SAqnfF+1JoEE\/PM8oEX6KOR7oVzW5iTIDnKYfnhjIkC6uyWhIl\r\npEFhFKuVtWmdk+qDDm6nPoeFyEjE3a6yc3jgrTWCRoXFDtKV\/beyCl4M7QNs2fifAfsHrpX0+LpG\r\nDLKJMa1ZAQpU6CcnolSBMbff0eGvwWGEofKuM\/8C9O4vSA8CA9ZE0A+sIwICeop+LcppGli3H7Be\r\n\/jsVETo3Zf2++kbBNbrzBqgEVhk9FIcZoVmGSlwmxF18RCMjRiKH4dbmwJZ9NeZ\/VAkCk1aX1o+E\r\n8NbcBPlJvLwBikMlKLv5H\/BORrbTdzDzwbdT7Ah2LIZB3ZqtXEcwy89UsKXjQQUGuGewwJC+wADn\r\nHxPygjyRJkzOdwKMYvhQchBHmhjZUjZ0BfafRVN6BnvCthA1sMxqxkuIBDCuDvO1e9Nh1wT7KcL0\r\nVKPX9vZSMs9OHghiSWcyeM6XH\/KWH3rlyw815dsVeZAQom06X\/ikyhzJcjxMCQfQqSUsFb8yqaXC\r\nKEkVLFgkEXsXC8OliaiNqSWrB6\/K2US5aa5PHWzBaxFChESrm5g4veskdt6oMk6l\/lhL0EUrgrk5\r\nOFBIUcfHYENS7rjzGYPHQZaAcbUbwWNehz4bSzmarVpX5O2wQ\/Uc9nhcWEEAl3eHGdW\/JHmaUk8W\r\nGHCO8ysjyAcobyzIrOfzjOXNRQillriFMwQ1I7MVWzUpgdHBjtMnJpoikRrs0MNHjQxd5YxR6Q0s\r\nSAqSEGuPU7ajWiIxqukRz\/\/0+lSz0e\/Gw6FzHnU7KknSGc7hk\/G1v+m2iUEHfmIiOh+x7iEVuhM1\r\nwIVev+CpMpmfkIkUmerNGiqNRu\/lfsaOoBp8B5591EgDQwESEYtVfSKYiTr31Yr11J8mKJPhXQKN\r\nA5xwDAwWEjX2+yLlVSVotSUH\/sHE2\/plU1RgQGj5VWAAWhwQBw\/wMLYfk1rNjN4sStVXK4scIn2U\r\nFFgfQvA89M3HgUz\/6RNUOYuNuVTUQzOgTil9iRllXtNSce59OtpPXwqTQBWVwhybIPPYaJw6H1Wo\r\nRDWKOWOJcxDEgRjkjyyB+aEvwLMH0kDO1O0V7DRe2bzsJXMAeAKkUn6iJms6HFzVGoEgn1SRPkTp\r\nUN1rVXWLgO1z2JEHRKorK59V\/3oAd5WT5G5E7OXjhpVKSF4RN8NUm98UZ8P9r110oEZFZQHEtgcz\r\nzapflu0RQvYQCsGhSnVWRd1q0DkZ6773UhcSik3HZOowMSoIFVNnqI4URmKyUR\/7DhIowBfRJSy2\r\ndkjHetgXLLwjn9kZPCRFhe\/342uqfsMzCpEA6TUhf2BxgI1UVQqrrPqiY57l\/dXyKG2sfAFLKnJN\r\n9AUVIaF25jTUcwjx4hFXOY\/vdBLQYqG2U8lnhk3o9EF1Emp9IkZ2xJ1opaIrzJ\/R1XO0niyhTrQh\r\nHLFUFdJvRd8wHCF6qM7vBGu\/4GPVUkCIuNnOap6qXxuhhGlhymqTqDrpHtYFqBwL6LBV7TNVRMlu\r\n66QHPqW1BlKG6u5gyBN0BcVavhPpAJ2EmM2ltG7+QKuqB+nh\/T+1xapM\/WNtcdHZbQFv62nyN+iY\r\nWs8giLmrFM8rJ4yXqTlDhQDXQX9V1+93hn1CYjHaIH2URR8RK\/3VLzQX1FkwdVK\/vBuz\/Y6hlYe7\r\nLgjRDVkIP9JezA8mctWPo8Il2b\/qv0ZQInRoHGooTqerVuHnzbzVzf71OlnPoOf9\/b\/9\/BfeVLPm\r\nCUiEHL7Hu79\/\/r\/\/ePf3z\/\/3H+\/+neH\/V493\/\/PV+R7v\/uer8z3e\/ZPh+2QmEdTfMEuAxKvfA0+G\r\nmHpwpUY18EJssmptd4FEYOwL4n3Ex60NtJZ+rda+xZLlhsgp0FLF1R2lKjP1\/YuXBGI\/hQ2vwrOB\r\nbjRj9XtaQR8uTYhT5ww4UecWStvJKRTGTs3tDPHXqfmxNB9CySn3YeehuGXBDe08tIwSlB1QBSRG\r\n\/u62U\/ATbiBqIS8BwjTVQlytoC2rM3Op0zvCVnRyz2IAyx2hBCTzb7gnyIFgsyoCnco1qyIYVkXA\r\nW7waExF33QnRBFlNxwffYSjQCNlE\/Ho7DOXheRBEqqU9dBzKxqh2B\/ZU41IJT2ooyH4lHXSWsE8L\r\nYtFX511r6+w0sh4sdrXWb+VIVWf88iZWoXaTzlXfhnIET1wber+EwmHCQS10WkUozgHp1JdydKgK\r\n7t4G1dZxmGStCi9KRqEmpV\/XcZVIABlCBqvIeUEQ1ErPKqE6\/fYqhvGqfVWW2Z9qfc9tFN1AEQKr\r\n160M02dHpLeMJtPpX1ZTzS9HhNvO51k0wgMwqVgHWxtKu6oWkpViZF0abk8sW9vQme5bUHWNdFnh\r\n4SgrWeEhRIqhVXC9W1CNagb9Cg9V0WmH2boywKn\/br6v8DCpN+mo4ETlvg0iPfJWhfRWItA6VLqV\r\nQ2bFhVPUJnOS8jGV2G9Mrd+p1YEdrvZTxcg2zdnYuq9l6lcdo2II2tSKxyP6V7KBmWUXWCIGr+iu\r\nHp+jyyrUpNug6T8lxkcvUWd+G4mPrNlKN+AaoVsSPKmO2EHxdEjcUaOoU7QnUK++bRXbZGi77vKZ\r\nfeqWAXUEo9uCzpoQsPA8aTDJb9YNy7ZzmPp04YS0MYoKp2IabXar1VKr01erpe75aOVrUX1W7CCm\r\nDGOoupLHoxGhhXwqDLUrI9D+rab1X1\/\/gWcP7EIft4aXYiSM6pKPmr\/2fZ8qHjQgZEqSXJ3nopXV\r\nIYemZSxKD0GBdFyhk4Koo+gMIVADdkV0IZKDLlGQzuGJ2JHKq1tHdWd0V4E8qNZPHALqMtS2im+p\r\ncl9iG74KIKI00T4ILmhb1rm7ikl9Grir0Sj8uRSwy7Wkk16rgxONKGqcU7dHUx3c0PUNGLBsPI+i\r\n6wN0rPu2NZvZpRKsbFCaxT2VGKthLRNPlvpw1RQY1NhfmtW4qT181KkMfYwjqW9cuqb7rO4EhlV1\r\nIYqb6mzuugalrxjUeHtUL6VzFMgWhq1+JvCuKPmwPiEjMcyvKwyyz6ksCCQC3dusWf7xugLjBtlQ\r\nNF\/Nss9R0N297gDxqjaDlCjvriNESKRXCa6KkHjQUD4GsFEO\/1bVCQeVcxOT1gYCgBMUvhITSigB\r\n80\/F1Lic7jEBzYlRm+F5+JFu1yC0zc4qJ925o9xbTl3F6BugEts9QI9oqsFbA0bZoa8vRYkpnP1c\r\nV2u+u3ioMlFtwqHDUBwEgojDQblkouf0K+kaD6\/edJ1XKrF7O6\/fVuueQMhb69hKht3Ho+pVSrOF\r\nbb1OLK1n4M0O\/rumDuRcXXCT1D+jA2GVKmSdPrnexhRvfUrwsgAXy40jfwXsC5mkkhwZ59a5iBX2\r\nE3+IrRhWjtmrvklW4H6aVGKzDoGvSUUdAmpSUYmoMns6AR\/WPNOL9RwA4T7\/9BygKKPAX\/nPbs3E\r\nuhWmQLqrSPdg7p3N0+0Bi6VWomsTmlV3A83mVWmJ5X\/NBDgPLoIeU38KGrNVEQ8oPkQcPtvHRsO+\r\nsSWQFGbUnkJQPmCBwupPFW2TtGtuTvVYqVOmgQ+4SQpoNeIrVIQdaDrpHTB3PjrrkBOsXhUcvyoN\r\nO0T2wMdvFZ9W1f8sdX8DjzKxeHjzht5XwkOzq3eGrj+xuFqvNaegNbvui1FNelNzSnLWxGp9L\/1f\r\n73v5z6\/qfOHhrTld8IYTZLM3uYRPKXuN6BAVm9YLh\/M9jD80p\/jVWeeky52yms6YkSNur6iSGaYK\r\nbUrq+AsJyyvxIivTSqOorqWEUaTo0Lpbh\/agmUlsdUHrLhfHGHAQCcWzVcyAxOwC06\/fpSgtAUv5\r\n6XfZddsBa8Co41KHiuwvSbA5tago5Xp\/1e2HH0pxANCv28qrUYIN7VhhV7dVVXBsSddDnXqe2lOq\r\ns0NjlnsX9T0edVLrLrapGkAoae+dxegXs4Hm3awawvFzD4fqekBy9SGv4E7K0Ni01fqvOhPIJ4jn\r\nAQBfAOCY1OoJXHvdy7Wv1\/STutJU3hvU8rnUqc3U2BB1voSm7vrT1QAbp0r\/4MSI77TqUWHv6ils\r\nQCb48OW3vC4rAuvwEOUBHfiEW6srL4iRpsxO6RRis5fN2jVg98J4Nbqol1X1G\/t3ows4AZsGJ1z4\r\n6XQBuFgBQugoN+s8giiqSlj19HzdWtbj8qvV9us7OGqPUlcfi2393RBkwG\/rgFVN6Sy24pBX9XMY\r\nqavdOmt9WiAm170NuC\/ER6VCePc9z6lZGadR9kIjQ1kMpDoUT4Vhut7jKiFvgv4pB4hPv6Y4BdYG\r\n6X7mDVMPDoayY9dhZYrVKI7VOHtMTg2W0HQd8cDNbIk3EVxpx6+EGcQmmqAN1AeZRYKwhdHw5nlq\r\nVJVDuqovKJNhHt0qFoZuhFDHk39WvdLTUP2KSsSlx2D76LWq2kr2fnd15OusMHarrVI3l+Bm6aI2\r\nYq4KxwkBukqHEKWilXS2YAtw3Q6yykbtuHl2nGrK1W1Guveg7GGKid9dg1alMmTlU93JwO6zNnK1\r\nNZ+e3VPBrYQgY0DIqIb167zFW4sVowbLs\/Cr93UZV928ZBPcuuNtGgt9OvEz0Kwi+ytD3SBWEUox\r\nUBmwA3WyfsgIQ8OHdb0WlqZ6HLhqZowqQbzJiQGLEyVrR4Zwol3BfHyB6Gj3ftjBrx0oZqSazoGn\r\nksY7YedKv6oy7UzXVRGKxGMAupdOpaZSgkAL\/OAQuqcQ6ykNtTrRPOj0Tkf0Ted+RzUKOrYSz8Zc\r\nsy7I22VAsAuBrMNFQkdV\/twmJfmZg3oz8hbzGH\/Da50ayYrfDCrwgPi1tuNG2Vrb7Ta7XOrSxWI2\r\nSEJb1LUUvwaptneizHBq61sq8bIp28U8UtKWDkPDDoHCUlPj0lVTdiEdu8rCVtFfguzDIfyarigB\r\nqmsClFG1essmOGOH1esR9k8V7tdGjs+vIM2KKGvjwAR1HKBWgOJ0KQLBCY4jl80FoW938XjdEwq7\r\ntnwbMPO1qarnigCf1aNPzPTn526Emd3SfaN2OYJqgJR\/hHSMCm6bx+vuNCSfbilQtkAZclXHLp4j\r\nttjeWwmKNJ6zevcQ0Vi6vuHqRFy3CcjA8VgsGyK21NpivbIYdNM1gLvYtUiso9eVg+yVG2FE7dGV\r\nOzOz0Aidqp33TBe226cuadHlq+NTzgR62bpu+8L2ANglsnB1lwYb4RHtQHOxS8IwH7sj4zt3698V\r\nGbr+yK7IIIBKimb1+rH2ygesoXpI7HsFXdPVwsGIgXOGLEzTqZWaT36u7JC+vlp3Oy\/+7nDY1hLC\r\nTCylESfKs2eiJCO6upkJD97JSMFRkVqA1lb1ft6XVcO0VHSGhkA9KSNMCIJTu6erDVfdPiCQdM0W\r\nX5H0Eg8\/BxJfVccY1ozupUkCRKv\/qRkd4feHyx9x5Xx0XgDgq0MFYzpLp87sFSHn657eqtzVe6bu\r\np9QljRqzsse3jKi71s5TZygRWnUuQ926iEg5qdoXVOR5AOxi5d1Fl7olIhQ+WlWpVXNx2S5JtuuF\r\ncAc4rq4QVCLBaDDcVL3puhYDuMN4eKhXBNYtf9aMpFpsprGdNXQRw5kPFAyx0yrKTiwue8W6Yg3j\r\nCFwABkV5lY0jsO00YQPExgzNBJjuVxBnd6+h7aZuphzotgwqG6vw7KMu9LM01WR1MMWr+yDUNKZi\r\nKewUvgdh1\/V6FhO1TBWersvWgq6JSt9FQqrcuuJQxjuGUiKITwgcPBK6o74KXTflrgpYD8Cp0gTC\r\nlrIqdk2Q5KeuCSI0RZWPEh8IUGqF\/cqcUyrzyGWCTpagNVAXtQvp3sJEiCUqFnHtp2Pe9UTcEBk6\r\ny4u6vxD6fu2KGLUz4hPQh6Ss4xIZ3dC1dJVURxCsoEuI5NXLigxQIUhiJvZ0FdMqyj4R\/USMjlqa\r\n7eRkdxZbPQjSPzoqIVYSYRJBHXGgSxgBAPGUJpiUD+ksrc6iuyQANlXMDzXg2C3DE+bQrBXhKvi8\r\nJr+DVT31tw7V+FTCFkiEAeuyALtlTBYA2VyiH1cl1MDI+W4xK3arVZ7frVagO\/JbAUFRcrAr6rZa\r\nKjXPupwp6ToauwHs6upMP9BrkreVddBtTawHSNO\/WyaKLkYwVqRbouBRdpWYOnyWynLtzEqp66\/k\r\nwZX3YYuuYbTbklQmEDuBQPVdgUc09XfAGHRqgfqx69CmHwRLXYdG5O36v+s6NO1iLMmaay6QmKKO\r\nzPGPodiizoAGpOdobWeqLjiqOeLzdP+ODS8+paG\/u3v0iiAljOpT5k\/Da9\/wsK71c8ua8s\/gBjRL\r\n1TI6v2ZuUitOzRvKuCp1WmEhP6Yw9BCVbbSpOqwZpNtz+9xYdcc65YQCNlD7K\/YSRTlf64Su8oRl\r\nCEIJ1UAXbylESTXjRdVYCVv5fVZvtOQW5KjYlXWglvvZ7SY+JK\/Q5YnY8bITbl2wzm\/tSF\/NXjjL\r\nSeKcBV8lxOmyVZ1x5pedLl1u5h1XV2RaU\/HnHaz29XEMb16limsdh+I98+FURb1iotP32vGvq5Ah\r\naIP6QnUJsfXgq\/rPcKfoxExlHrqEGNzJlu6wVq6cFLoKMmlkXbdpR9BEY7UR+Kbyp6UrZZIOltF\/\r\n+ERqumoC21TVDXRZDTPin6q20o0LhPQ0Qyb2V1Xl+lKU0AcyKranqg0xmaEb4S7WMqPCWdedMU9p\r\nLBAXj4YlrFLEGeBHuhYQHe2z6Y+uJnEVrA67fHA3WDccRXcs6OoYnLOXqqsHq+qx1Hja7CSXxdbN\r\nB0qx8pCu7aq6QAK6UHX56EFH6NrWC33Q0YT1CamPdU1d8QZpmflEVdBCj5Fx25ob0Z661Ld1u4+q\r\n6g4iIILnFLwI0AYtveZmdcxdRRHKx4NtRP8bdQR9BHmgbFBSL+oGyD55xU26UfHTbRJ+LX6FwHZv\r\nmZpp2f9Sxs+9ZQ4i9N+mLiz5BkHzED4VkhK4JxGoKA2uky\/RaKfbgqZqzpIq1XBBVXeMUnIZRmTg\r\n10quN4VJdVyqACFZJs4rm44Xx7iZr7PUoO7Y1gn1U\/nQn26G6n+9GQrbh5YWXc6aVOTKW3WnKw+a\r\nA6RRCTWIj3YDNhAhqCDQcEfdLGTFHQR1ySrsstnNy0FFcaq\/BKJ06nvVUqXD\/KV+E3i5ijh04ieX\r\nebpRUzcpCnmK7g57Kk0YXV1sZSp9pCYN3Vcj5q8Lk1W8vFpWB4zuh0noaN20icbGl3RFCBx1XE8c\r\nFsdZhPCsjABiQ9V+CjTFIb11QZPYGosLmz\/qkrWGqQaCgq6qdKpZl9oSeRnaUym3Lmw1gFoJxQO0\r\nOEwsK9nmdYynO8yfLmuKb6qcUmVTqjZVXYR0J79QrXSUltx7EH\/wdljyXd0pVHudNe6g80Md8ie7\r\nipXVgDtW3VwOAPMCNcmxMF614nZ1BMMGdoopv+owd90qtdX2ElS0lsSOgi61hkrELIzQJ6p6d+o2\r\nu6WbopX7OHZTdM9fo4zrqkfKuoqy63IYFcrjZAXFFK39qa7265OUueW3qCqEZCu6KEr3LILBsHf3\r\n3bmgi5dhe1OX3B71JVYVsoRqFy\/rLGbbZWTq\/pbSWHalNmxIBd9D9wsX3e2jEke4cxy6LgmSExpO\r\n8tSqbBwifQv\/3ZZJLNC5w7Krs6MZpdUK4P1HlWagud26HFFdQDPcbkA4oYVPN0vA97SxEnkW3tv2\r\nKnWCPqtMw866dEyvC+JgFsAsUQR1kEfFDaE6RQ0+ql672MVS1G92Ry4GpHzerF88hLOyOe7aNagg\r\nKDFlVsBYFwxCSlgPNYkJ5+FPOluoaOGk5LNS6tLkuodIikEZ1ufaUlvK1wxkB6KsODrnVBgXVk1c\r\nqKrpxFFUDRzZXFBAtFUd98qaBKACuuiqqpdajdkOmep3h7NgYtnEQCevrWBvdJc+b0HZwnQZ8LAu\r\n8igDRs24\/\/L2Zh3egm7u\/wE0r0eqHiyGowAAAYRpQ0NQSUNDIHByb2ZpbGUAAHicfZE9SMNAHMVf\r\n00pFKw52KOKQoTpZkCriKFUsgoXSVmjVweTSL2hiSFJcHAXXgoMfi1UHF2ddHVwFQfADxM3NSdFF\r\nSvxfUmgR48FxP97de9y9A4RmjalmYAJQNcvIJBNivrAiBl\/RjwCCiCMiMVNPZRdy8Bxf9\/Dx9S7G\r\ns7zP\/TkGlKLJAJ9IPMt0wyJeJ57etHTO+8RhVpEU4nPicYMuSPzIddnlN85lhwWeGTZymTniMLFY\r\n7mK5i1nFUImniKOKqlG+kHdZ4bzFWa3VWfue\/IWhorac5TrNESSxiBTSECGjjipqsBCjVSPFRIb2\r\nEx7+YcefJpdMrioYOeaxARWS4wf\/g9\/dmqXJuJsUSgA9L7b9MQoEd4FWw7a\/j227dQL4n4ErrePf\r\naAIzn6Q3Olr0CBjcBi6uO5q8B1zuAJEnXTIkR\/LTFEol4P2MvqkADN0Cfatub+19nD4AOepq6QY4\r\nOATGypS95vHu3u7e\/j3T7u8HfIdyq+EthnkAAA0YaVRYdFhNTDpjb20uYWRvYmUueG1wAAAAAAA8\r\nP3hwYWNrZXQgYmVnaW49Iu+7vyIgaWQ9Ilc1TTBNcENlaGlIenJlU3pOVGN6a2M5ZCI\/Pgo8eDp4\r\nbXBtZXRhIHhtbG5zOng9ImFkb2JlOm5zOm1ldGEvIiB4OnhtcHRrPSJYTVAgQ29yZSA0LjQuMC1F\r\neGl2MiI+CiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjIt\r\ncmRmLXN5bnRheC1ucyMiPgogIDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiCiAgICB4bWxu\r\nczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgIHhtbG5zOnN0RXZ0\r\nPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VFdmVudCMiCiAgICB4\r\nbWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iCiAgICB4bWxuczpHSU1Q\r\nPSJodHRwOi8vd3d3LmdpbXAub3JnL3htcC8iCiAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRv\r\nYmUuY29tL3RpZmYvMS4wLyIKICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAv\r\nMS4wLyIKICAgeG1wTU06RG9jdW1lbnRJRD0iZ2ltcDpkb2NpZDpnaW1wOjk1Yzg1NWRiLTI4MWYt\r\nNGQxOS1hMzk4LTVmYjMwZjU1MjhmMiIKICAgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDo3NGY5\r\nNGFlMy1jYjg1LTQwNWItYTIxNS05MzUyYmVlZjdmZDciCiAgIHhtcE1NOk9yaWdpbmFsRG9jdW1l\r\nbnRJRD0ieG1wLmRpZDo4MjkxMGExZi0wMTdlLTQ4YTktYWQzMi1mYjkzZGYxZmYyMzIiCiAgIGRj\r\nOkZvcm1hdD0iaW1hZ2UvcG5nIgogICBHSU1QOkFQST0iMi4wIgogICBHSU1QOlBsYXRmb3JtPSJX\r\naW5kb3dzIgogICBHSU1QOlRpbWVTdGFtcD0iMTYzNjAxNTM4MzY3NzQ3MCIKICAgR0lNUDpWZXJz\r\naW9uPSIyLjEwLjI0IgogICB0aWZmOk9yaWVudGF0aW9uPSIxIgogICB4bXA6Q3JlYXRvclRvb2w9\r\nIkdJTVAgMi4xMCI+CiAgIDx4bXBNTTpIaXN0b3J5PgogICAgPHJkZjpTZXE+CiAgICAgPHJkZjps\r\naQogICAgICBzdEV2dDphY3Rpb249InNhdmVkIgogICAgICBzdEV2dDpjaGFuZ2VkPSIvIgogICAg\r\nICBzdEV2dDppbnN0YW5jZUlEPSJ4bXAuaWlkOjdhNjJjMTVlLTVlY2QtNDFkYS05MDRkLTZjNjAy\r\nY2M0NDdmZSIKICAgICAgc3RFdnQ6c29mdHdhcmVBZ2VudD0iR2ltcCAyLjEwIChXaW5kb3dzKSIK\r\nICAgICAgc3RFdnQ6d2hlbj0iMjAyMS0xMS0wNFQwOTo0MzowMyIvPgogICAgPC9yZGY6U2VxPgog\r\nICA8L3htcE1NOkhpc3Rvcnk+CiAgPC9yZGY6RGVzY3JpcHRpb24+CiA8L3JkZjpSREY+CjwveDp4\r\nbXBtZXRhPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAK\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAg\r\nICAgCjw\/eHBhY2tldCBlbmQ9InciPz4EOByZAAAABmJLR0QA\/wD\/AP+gvaeTAAAACXBIWXMAAAsT\r\nAAALEwEAmpwYAAAAB3RJTUUH5QsECCsDk4V7vAAAD15JREFUeNrdnHtUVNUex7\/7zHuYAVTEFG4o\r\nDPhAEBgzwiDMrK7cWxqZS2kpd3l9UXpL7LK0q5WZLldiJV200HXJFbZ8lXbD20OTh7rQRFRECRgT\r\nA1TCBAbmfWbfPw7nzIxYDAgjtP\/cc+ac\/f3M77H3b+8zBB5s0UmzKQkYDfJAMOAXCOLjD6gGgyhU\r\ngEjCXcRaQY1tQNuvoC2NQFMd6I0roPU\/oix\/D\/HUWPv0QVGTE4OZ8HgdGf0IGM1EQKG6txsa22Cv\r\nOQP6YwnsFUUh504UXBlQYKLnLqOM9s9gIhJd+tnGWtiulsNWVwX25k+gv16HvfUWqEEPajNzAxLL\r\nQJRqMN5DQAYPh2jYKIgDwyAeGQGRf5DL\/ezlBbCX\/g9lu7NIvwYTs2gNZSbPAhmuEfos5YWwXCiA\r\n7fJJsE3193R\/kV8AxGPjII1MhDTiMaGfXq+B\/cQ+nP34bdKvwMTMW0GZqakCELaxFqbjB2A59V\/Y\r\nW37pE1NnfIZC+vBfIX80WbAker0G9qO5OLtrC7mvYKIeT6KipJcEl2Hrq2H87j8wl3x594cp1BCH\r\nREP84DiIAzRghgSC8fUHUXqDSGScOKsZ1NAKe3Mj7LfqYKuvge3aJdh0ZaBG\/V3vK4t9Boppf4Mo\r\nIFRwMTb\/3zj3fT7xOJiYRWuoaEY6IJaCmtpgOJQF07G8u5q\/JPpJSCMTIQnV3tOvaK0uheVCAaxl\r\n397VLeVTUqB8dhmIXAXYLGAPZvbYvXr0Je263ZSJnQEAMJ\/+CoYDmZ1cRjI+AYqEWZBETrlD3BnY\r\nfioH21AN9mYt7M03UVN60mUcGm0cZXyHQTQsCKIRoRCPioAkdKLrfS4cg7FoH6wXizq5mDI5HbJJ\r\nf+Gsp+QgStfOJX0KJmpyYrA4ZZ2OaLhBtuW9BXPxvs5AnlrgYh3mHw7DUnYElfu335Prjnl+CZVG\r\nPwHZQ9NdrMj4zc5OgGTxs6BKeYNzz5ozsOWt7VZ6J92JJ+L5G0GGa8A21qI9dzWsV867uIxyxquQ\r\nTnya+6WaG2Eq2A1T8f4QXfnZXp1vhETEBMvjn9fJE+eC8fXnst+Zr2E4+J6Li0mCJ8ArdQNE\/kGg\r\n12tg+2SV23GHuA1lQSbI0AdhqzkLfU66i+vIH02G8oVVIFI5qNUCY342KrJXe2SWGp62gSqS0kAk\r\nUlCLCYa9G2E6fsDFtdQLMyHWxID+cg22neluwSFuuc+irToyXANrZQn02S+DWkzC515z10CeMFtw\r\nGcPnWzrFjL5uGm0cVT63QnAxU9EetO9+2yFSKoc67UNIxsRylvPx8i7dqksBE7OLKNFMhK3mLFq3\r\nLhKgEIUaqoWbIR03WYg3lz\/Z5FEgd7ax8zMoH1csl06gLWelkOKJVA7v5R9zllNzBmfSEkiPwfDZ\r\nh22sRWtmquA+jLcf1GkfQjxyPOxNddDvzEDV0S\/uKxS+hU2dSdULNoHxC4Tt6kXos1+GvbVJcCvv\r\n9FyI\/IO6zFbM707vO1Jye+5qAQpRqAUottqLaMlM7TdQAKDq6BekJTMVttqLEI8cD3XahyAKNZcQ\r\nWn5Be+5qTnjsDMQsWkO7BSbq8SRu8tbhIs7ZR7VwswCldeuSEE\/HE3daTelJ0rp1SQgPR7VwsyO9\r\nXzmPtry3uEw6Ix1RjydRt8GIkl4CxFKYT3\/lMk\/xmrsG0nGTOffZ\/gp6Ow33ZtOVn72i3\/4K7E11\r\nkI6bDK+5axzzquJ9MJ\/OB8RSTqs7YGLmraBMRCI3zT+Q6ZKS+eyj35mB\/mgpd7Mc\/c4MbvwJsyF\/\r\nNFn4zHBgM6ipDUxEImLmraBdgmGmpnJfPJQlxBWRXwCUL6wSXKs\/xRR3Yg7vOsoXVkHkFyDEG8Oh\r\nLBfNvwkmZtEaSoZrwNZXuywIlTNeBZHKYf7h8H1PyT1plz\/ZRMw\/HAaRyqGc8arQbzqWB7a+GmS4\r\nplMgdgHDTJ7FVRC\/+4\/L2kc68WlQqwWGz7dgoDbD51tArRZIJz4NyfgER7W0QyuvnW9i53Ikvw5y\r\nrqconlrA3SA\/2+24Ehy1iBIfmWcUt5yH7lwRcSfehOdnU+WMV6B4aoGw6DSXfAnF9MUQDdcgeu4y\r\nypdJBTCM9s+ceTmtMyTjEyAJ1cLe3OjW2ic4eD4VLXwbPlo\/eNLfVKW59PyqtC4fWZG9mmgT51JJ\r\nqBaS8QkCHNPxA\/B6bgXHYHeWw5WiJicG81U4y6n\/OqwlgTMvU8Fu90YY9w+oPAwFAKTaOQh\/MZi6\r\ncy2vhdfmrJmJSETU5MRgAQwTnqDjC9fOmYgvMpmK94e4VYcNC3CYoEebDJKwVLeu5LVIIqe4ZChL\r\neSGnITxeJ4Aho2M5chcKHG4U\/aSwYu7PE7meTPzMPxx20eisnYx+xOFKTEdFznb5pMM8Iztcq+yI\r\n2w+lPzfCdl\/kmmH9+bLbV\/OaeI3O2nkWTHTSbAqFCmxjrVD9Igq1UJrsTjmSHn0JrXnnYbF7Eooe\r\nxn1rUZHzmdvj5DVJQrXCApNtqgfbWAsoVIhOmk0ZEjCaI3a13JHDQ6KFwnV32pUrRUT3ySNEvz4X\r\n7bf6Hgltq0TLulm4mNP9nUheG6\/VmQEJGA0Gw0ZxnXVVDjAPjuP6firvXi128TE69sX5FA2bYXoz\r\nA82lTaB9BMV2+TPcXvU8bA11CHnxGzp28bJuPYrXxmt1YTBsFBgy9E+cKd38yQEmoGNHsaG6W4Ml\r\nAeOgmrcN6oUrQaz5YHNexO3edi27HoZ9GWj54B1QazxEqQXwnhcPWcDQbt2G18ZrdWZAhv4JDPHh\r\nquz01+uOtDsksOPC2h7OK1Ix6J08iEYAtDgF+jd6x7XorfNoXj8LxqP5wNCVkL\/5Pnxj\/Xo0ReC1\r\n8VqdGRAffzBQDeZ+iFbHyPktCXvzzR6LIEMmwPdfn0IenwT8shmm15eiuaSpx1nLUpqL22+mgG2o\r\nA4nLg\/qtVHg90PNlB6+N1+rCQDUYDOReHC2DY1+YKL2F9cU9\/cSMH7xSNsF72esgkmKwuYlo3XUK\r\nJnN3MnET2ncthT5nMyiSIFpYgEHzJkDK3HutxlmrCwO5FxgilnKdNsdo+Q323mqS8DkYtPEQJMGB\r\noCcXoH1VFtoazF0GZrahGM2rEmE6WQyMeB2K9Zvg28tLDmetjjM6UjDwUCOqEHj\/8xCUyXMAw0cw\r\nr0tBS3Ed2N+YsJmKs9C8bilYQyCY6Yfgs3YOlD6emx0x1GbpoOREzmruo8fJoJj2OnxXbgKRVILN\r\nexrNW\/JhbHEKsC06tG5JQXveR4BkDiSrDsH3mZA+W4M5a+UZUJsFYmpsA5Fwx7toi6nD11pBpHJo\r\ntHG0L2q7Ik0SBq0PQesHr8BWlQFDRhaMD0wA0AR64xR3UdgmKBYlQanqGyAabRzltTpiKzcLpsY2\r\niKn+FuA9BIz3EGFlbW9uBOPrD8Z3WN+5ls8Y+Kw9BFPpVzDtyQF7I5\/7wDsVkvmp8Ar3g6gvXaVD\r\nm7250dHnPYQDo78FMXv7BkQBYSCDhwM\/V3IX36oDRo6HaFhQn5cL5NpkyLXJsJvNoJCBkcEj9Rxe\r\nm\/1WnePHGjycC\/q3b4CxN17ruHCUY2pcX8P1jQj1XLCTySDyEBRnbbxWZwb2xmtg2AbuA3FgmAPM\r\ntUtc36gI\/FEbr43X6syAbagBY62t4DpHOiDYdGXc\/OOO411dTyfvo1J79zIpr43X6szAWlsBprow\r\nn1CDHiL\/IKHUR416WKtLAXDHu9weW+E5mO8LlSaYCj9z+2pek7W6VDgmIvIL4E5eGfSoLswnDABY\r\nf+RSpHhsXKdSnzT6CffrMceeIu27TsFk8BwSaqhD+641qDx2xe3wxGtyLuXy2gUWAGCtKIY0+glI\r\nIxOFTXxr2bdAcjpkD01HSERMsLt1X92nUwg+7b+xJSQiJpg\/eWUt+7ZTKddacZxLBgBgPvf9NACQ\r\nRjwGxmeoUOqzXjjGranin9f9UYIur8V64ZhQymV8hgpH8M3njk4TwOgulB4RCsQP\/1W4ibGIsx55\r\n4tw\/TDbitfDanDVbyo5Ad6H0iAAGgLAt63xUwnqxCNbqUjC+\/ghP20AHOpTwtA2U8fWHtbrU5Vww\r\nr9l5a1oAU\/nFDsLWV0HkHwRZ7DMOq\/lmJwBAkZQmrC8GYtNo46giKc1FE8C9hyDyDwJbX4XKL3aQ\r\nTmAACEc\/FNP+5mI1ljNfg0ikUD63YsBai\/K5FSASKSxnvnaxFl7rne9BuIC5tOtdzmoCQiGfkiL0\r\nGw6+B2oxQfbQdIydnzHgrGbs\/Awqe2g6d0D64HsOF5qSAlFAKNj6Klza9S75TTAAYDz8EUf42WUu\r\nGcqwdyNXDk15A2FTZw4YOGFTZwpnfw17N7pkIuWzy1w0\/y6Yy\/u2EUvZERC5CsrklQ43O34ApqI9\r\nAAD1gk0DIt5otHFUvWATN\/6iPS5HXJTJ6SByFSxlR3B53zbSJRgAaD+QCWo1QzYpCbJ4x3GJ9t1v\r\nw3LpBBi\/QKiXvI+QiJjg\/jyRUy95H4xfICyXTrgcoZfFz4Js0l9ArWa0Ox3A7BJMzelCYtizQXAd\r\nSfAE4bO2nJWwXb0IcdB4eC\/fruuPlqPRxlHv5dt14qDxsF29iLYch+VLgicIr+sY9mxAzelC4jYY\r\nPhCbivYCALxSNwjxhhr10Ge\/LMDxSc\/tVzEnbOpM6pOeCx6KPvtlYaHI+AyFV+qGDtfa2yngulQY\r\nu3pQ1NbvqWRM7B\/qJQtrZQnOLX\/8d8fa5faJfsdr09j6Kog1Mdy5fKncYTlbFwsBWZXyBiLf2U\/v\r\nh2tptHE08p39AhRT0R7oty52gaJO+xBiTQzY+irod7w2rat7uvULayY9Rr2XbYNo2MgB\/SIXe\/Mq\r\nWrOW\/mZc6TYYAc7SDyAKCBuQr\/6x9VVo3fYPt6B0CwwAhERqn1D\/\/d3vJGNihbgyEF4WtVaWQL\/j\r\ntWn8yrnXwfAt4l+5VJ7wAie6X75evBKySUlC9ilfn9q3rxc7t3HzXqPK2atBJLJ++UI6tZph2LPh\r\nd1Nyn4Dh445XcrpQQ+0vf2FgKTuC9gOZbseTXgcjzB9mLaWK6YshCujYl7lPf3rB1lfBePiju659\r\n7gsYZ\/filvKOzTtP\/E0KW18F07G8HrtNn4MRssfMv1NZ7DOdtl56+491LGVHYC750qXy1q\/BOKd3\r\nWdTU7yThj0Iy+mHhmEVPGzXoYf3xFKwVxTCf+75b6bdfgbmzhT6WRCVB4RCN0IDxfxCiQQ+AqIeA\r\nKFRwHHmzgBrbQPW3wN6+AXvjNbANNbDWVqC6MN9j4\/0\/A2KXcs2qfKwAAAAASUVORK5CYII=",{"_pos":29946}]}}}}}');
                // @codingStandardsIgnoreEnd
            }
        };


        // 1st call structure
        // 2nd call body query
        $imapStub
            ->shouldReceive("fetch")
            ->andReturn($structureMock, $bodyResultMock);

        $trait = $this->getMockedTrait();

        $trait->expects($this->once())
            ->method("connect")
            ->willReturn($imapStub);

        $fileAttachmentList = $trait->getFileAttachmentList($messageKey);

        $this->assertInstanceOf(FileAttachmentList::class, $fileAttachmentList);
    }


    /**
     * Test createAttachments
     */
    public function testCreateAttachments()
    {
        $mailAccountId = "dev";
        $mailFolderId = "INBOX";
        $messageItemId = "123";

        $messageKey = new MessageKey($mailAccountId, $mailFolderId, $messageItemId);

        $attachment = new FileAttachment([
            "type" => "text/plain",
            "text" => "no text",
            "size" => 7,
            "content" =>  base64_encode("no text"),
            "encoding" => "base64"
        ]);
        $fileAttachmentList = new FileAttachmentList();
        $fileAttachmentList[] = $attachment;

        $trait = $this->getMockedTrait();
        $composer = $this->getMockedComposer();
        $socket = $this->getMockedSocket();

        $trait->expects($this->once())
            ->method("connect")
            ->willReturn($socket);

        $trait->expects($this->once())
            ->method("getFullMsg")
            ->with(
                $messageKey,
                $this->anything()
            )->willReturn($trait->rawMsg);

        $trait->expects($this->once())
            ->method("getAttachmentComposer")
            ->willReturn($composer);

        $composer->expects($this->once())
                ->method("compose")
                ->with(
                    $trait->rawMsg,
                    $fileAttachmentList
                )->willReturn($trait->rawMsg);

        $obj = new stdClass();
        $obj->ids = ["321"];
        $socket->expects($this->once())
            ->method("append")
            ->with("INBOX", [["data" => $trait->rawMsg]])
            ->willReturn($obj);

        $flagList = new FlagList();
        $flagList[] = new DraftFlag(true);
        $trait->expects($this->once())
            ->method("setFlags")
            ->with(
                new MessageKey($mailAccountId, $mailFolderId, "321"),
                $flagList
            );

        $trait->expects($this->once())
            ->method("deleteMessage")
            ->with($messageKey);

        $list = $trait->createAttachments($messageKey, $fileAttachmentList);
        $this->assertSame(1, count($list));
        $this->assertSame("text/plain", $list[0]->getType());
        $this->assertSame("no text", $list[0]->getText());
        $this->assertSame(7, $list[0]->getSize());
        $this->assertSame(base64_encode("no text"), $list[0]->getContent());
        $this->assertSame("base64", $list[0]->getEncoding());
    }


    /**
     *
     */
    protected function getMockedTrait(): object
    {
        return $this->getMockBuilder(AttachmentTraitForTesting::class)
            ->disableOriginalConstructor()
            ->onlyMethods(["setFlags", "deleteMessage", "getFullMsg", "connect", "getAttachmentComposer"])
            ->getMock();
    }


    /**
     *
     */
    public function getMockedSocket()
    {
        return $this->getMockBuilder(SocketForTesting::class)
            ->disableOriginalConstructor()
            ->onlyMethods(["connect", "append"])
            ->getMock();
    }


    /**
     *
     */
    public function getMockedComposer()
    {
        return $this->getMockBuilder(AttachmentComposerForTesting::class)
            ->disableOriginalConstructor()
            ->onlyMethods(["compose"])
            ->getMock();
    }
}
