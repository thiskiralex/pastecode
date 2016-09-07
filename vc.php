<?php

class VC
{
	public	$volume = '';	# Путь до образа
	public	$letter = '';	# Буква диска
	public	$keys = array();# Массив ключей Array( 0 => 'C:\temp\1.key, 1 => '...' );
	public	$pass = '';		# Пароль
	public	$label = '';	# Метка диска
	public	$size = 0;		# Размер в байтах
	public	$is_tc = FALSE;	# Является TrueCrypt образом
	public	$temp = FALSE;	# Временный образ (Удаляется при завершении программы)
	private $DIR = '.';
	private $VCDIR = '.';
	private $debug = FALSE;
	private $mounted = FALSE;

	public function __construct() {
		if( ! defined( 'DS' ) ) define( 'DS', DIRECTORY_SEPARATOR );
		if( ! defined( 'NL' ) ) define( 'NL', "\r\n" );
		if( defined( 'DEBUG' ) ) $this->debug = DEBUG; else $this->debug = FALSE;
		$this->DIR = realpath( $this->DIR ) . DS ;
		$this->VCDIR = $this->DIR.'SOFT'.DS.'VC'.DS;
	}

	public function __destruct() {
		if( $this->temp and $this->mounted )
			$this->unmount( TRUE );
	}

	# Мелкий дебаг
	private function debug( $msg = '', $lvl = 0 ) {
		if( $this->debug )
		{
			echo "VC> " . $msg . NL;
			if( ( $fh = fopen( $this->DIR.'log.log', 'a' ) ) !== FALSE )
			{
				fwrite( $fh, "VC> " . $msg . NL );
				fclose( $fh ); unset( $fh );
			}
		}
		unset( $msg, $lvl );
	}
	
	# Монтирует шифрованный диск
	public function mount() {
	
		# Пути что и куда монтировать
		if( empty( $this->volume ) or empty( $this->letter ) ) {
			$this->debug( 'Volume path or drive letter is empty' );
			return FALSE;
		}
		
		# Пароль и\или ключи
		if( empty( $this->pass ) and empty( $this->keys ) ) {
			$this->debug( 'No password or key was present' );
			return FALSE;
		}
		if( ! is_array( $this->keys ) )						# Список ключей хотим видеть в виде массива
			$this->keys = array( $this->keys );
		if( ! is_bool( $this->is_tc ) )	{					# Проверяем указанный диск TrueCrypt или что
			if( strtolower( $this->is_tc ) == 'yes' 
			 or strtolower( $this->is_tc ) == 'y'
			 or $this->is_tc == '1' )
				{ $this->is_tc = TRUE; }
		} else  { $this->is_tc = FALSE; }

		# Поскольку у нас разные бинарники VC под разные архитектуры определяем нашу
		# Надо перепроверить на x64
		if( $_SERVER['PROCESSOR_ARCHITECTURE'] == 'x86' )
			{ $arch = 'x86'; } else { $arch = 'x64'; }

		$CMD = $this->VCDIR;								# Путь до бинарника относительно
		if( $arch == 'x86' )$CMD .= 'VeraCrypt-x86.exe';	# Бинарник x86
		else				$CMD .= 'VeraCrypt.exe';		# Бинарник x64
							$CMD .= chr(32);				# Просто пробел
		if( $this->is_tc )	$CMD .= '/truecrypt ';			# Ключ флага TrueCrypt
		$CMD .= '/volume "' . $this->volume . '" ';			# Путь до образа диска
		$CMD .= '/letter "' . $this->letter . '" ';			# Буква диска
							$CMD .= '/auto ';				# Автоматическое монтирование
		foreach( $this->keys as $key )						# Перебераем массив ключей
							$CMD .= '/k "' . $key . '" ';	# если вдруг их несколько
		if( empty( $this->pass ) )							# Если пароль не указан
							$CMD .= '/tryemptypass ';		# пробуем без пароля
		else $CMD .= '/password "' . $this->pass . '" ';	# иначе указываем пароль
							$CMD .= '/cache n ';			# Пароли не кешируем
							$CMD .= '/history n ';			# Историю не кешируем
							$CMD .= '/wipecache ';			# Очищаем все закешированное
							$CMD .= '/quit ';				# Запускаем в фоне
							$CMD .= '/silent ';				# Без дополнительных окон итд
		if( ! empty( $this->label ) )						# Еслиметка диска не пустая
			$CMD .= '/mountoption label="'. mb_convert_encoding($this->label, 'CP1251', 'auto') .'" ';	# Устанавливаем метку

		$this->debug( $CMD );
		@system( $CMD, $return );
		
		if( $return == 0 ) {			# Код возврата 0 = Все успешно получилось
			$this->mounted = TRUE;
			return TRUE;
		} else return FALSE;
		
	}
	
	# Размонтирует шифрованный диск
	public function unmount( $force = TRUE ) {

		# Проверяем на заполнение
		if( empty( $this->letter ) ) { $this->debug( 'Letter not present' ); return FALSE; }
		if( ! is_bool( $force ) ) {
			if( strtolower( $force ) == 'yes'
			 or strtolower( $force ) == 'y'
			 or $force == '1' )
			 {   $force = TRUE; }
		} else { $force = FALSE; }
		
		# Поскольку у нас разные бинарники VC под разные архитектуры определяем нашу
		# Надо перепроверить на x64
		if( $_SERVER['PROCESSOR_ARCHITECTURE'] == 'x86' ) { $arch = 'x86'; } else { $arch = 'x64'; }
		
		$CMD = $this->VCDIR;								# Путь до бинарника относительно
		if( $arch == 'x86' )$CMD .= 'VeraCrypt-x86.exe';	# Бинарник x86
		else				$CMD .= 'VeraCrypt.exe';		# Бинарник x64
							$CMD .= chr(32);				# Просто пробел
		$CMD .= '/dismount "' . $this->letter . '" ';		# Демонтируем диск
		if( $force ) 		$CMD .= '/force ';				# Форсированно
							$CMD .= '/silent ';				# Без дополнительных окон итд
							$CMD .= '/quit ';				# Сразу выходим из программы
		
		$this->debug( $CMD );
		@system( $CMD, $return );
		
		if( $return == 0 ) {
			$this->mounted = FALSE;
			return TRUE;
		} else return FALSE;

	}

	# Создает шифрованный диск из файла $file размером в $size байт
	public function create( $force = TRUE ) {

		$file = $this->volume;
		if( empty( $file ) ) { $this->debug( "Filepath is empty" ); return FALSE; }
		if( empty( $this->size ) ) { $this->debug( "Size too small" ); return FALSE; }
		
		if( file_exists( $file ) )							# Проверяем на существование файла
			if( unlink( $file ) ) {							# Если есть пробуем его удалить
				$this->debug( "File " . $file . " is was exists and i remove it" );
			} else {
				$this->debug( "Cant remove file " . $file );# Если не получилось выходим
				return FALSE;
			}
				
		
		if( empty( $this->pass ) ) {						# Данная функция умеет создавать диски только на базе пароля
			$this->debug( "Password is empty" );
			return FALSE;
		}
		
		if( ! is_array( $keys ) )
			$keys = array( $keys );
		
		if( ! is_bool( $force ) ) {
			if( strtolower( $force ) == 'yes'
			 or strtolower( $force ) == 'y'
			 or $force == '1' )
				{ $force = TRUE; }
			else
				{ $force = FALSE; }
		}
		
		# Поскольку у нас разные бинарники VC под разные архитектуры определяем нашу
		# Надо перепроверить на x64
		if( $_SERVER['PROCESSOR_ARCHITECTURE'] == 'x86' ) 
			{ $arch = 'x86'; } else { $arch = 'x64'; }

		$CMD = $this->VCDIR;								# Путь до бинарника относительно
		if( $arch == 'x86' )$CMD .= 'VeraCrypt_Format-x86.exe';# Бинарник x86
		else				$CMD .= 'VeraCrypt_Format.exe';	# Бинарник x64
							$CMD .= chr(32);				# Просто пробел
		$CMD .= '/create "' . $file . '" ';					# Путь до файла
		$CMD .= '/size ' . $this->size . chr(32);			# Размер в байтах
		$CMD .= '/password "' . $this->pass . '" ';			# Указываем пароль
		#$CMD .= '/hash Serpent(Twofish(AES)) ';			# Алгоритм шифрования
		$CMD .= '/filesystem NTFS ';						# Указываем создаваему FS
		$CMD .= '/silent ';									# Тихий режим
		if( $force ) $CMD .= '/force ';						# Форсированно
		$this->debug( $CMD );
		system( $CMD, $return );							# Запускаем процесс создания файлика
		
		if( $return == 0 ) {
			$this->is_tc  = FALSE;
			return TRUE;						# Код возврата 0 = Отработало без ошибок
		} else { 
			$this->debug('system() return '. $return . ' errorcode');
			return FALSE;					# Иначе ошибка
		}
	}

	# Удаляет образ
	public function remove( $force = TRUE ) {
		if( $this->mounted )
			$this->unmount( $force );
		if( file_exists( $this->volume ) )
			if( unlink( $this->volume ) )
				 return TRUE;
			else return FALSE;
		
	}

	# Проверка на то что диск подключен (заглушка)
	public function is_mounted() {
		if( empty( $this->letter ) )
			return FALSE;
		
		if( is_dir( $this->letter . ':' ) )
			return TRUE;
		else
			return FALSE;
	}

	# Монтируем TrueCrypt образ
	public function mount_truecrypt() {
		$this->is_tc = TRUE;
		return $this->mount();
	}

	public function unmount_truecrypt( $force = FALSE ) {
		return $this->unmount( $force );
	}
}

?>
